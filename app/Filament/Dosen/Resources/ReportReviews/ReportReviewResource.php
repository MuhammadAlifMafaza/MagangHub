<?php

namespace App\Filament\Dosen\Resources\ReportReviews;

use App\Actions\PublishAssessment;
use App\Actions\ReviewReport;
use App\Actions\SaveAssessment;
use App\Enums\AssessmentStatus;
use App\Enums\ReportReviewDecision;
use App\Enums\ReviewStatus;
use App\Filament\Dosen\Resources\ReportReviews\Pages\ListReportReviews;
use App\Models\Assessment;
use App\Models\WeeklyReport;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ReportReviewResource extends Resource
{
    protected static ?string $model = WeeklyReport::class;

    protected static ?string $navigationLabel = 'Pemeriksaan Laporan';

    protected static ?string $modelLabel = 'laporan';

    protected static ?string $pluralModelLabel = 'laporan';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function canViewAny(): bool
    {
        return auth()->user()?->lecturer !== null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $reports): void {
                $reports->pendingReview()->orWhere(function (Builder $approved): void {
                    $approved->where('review_status', ReviewStatus::Approved)
                        ->whereHas('assessment', fn (Builder $assessment): Builder => $assessment->where('status', AssessmentStatus::Draft));
                });
            })->oldest('created_at'))
            ->columns([
                TextColumn::make('reportPeriod.internship.student.user.name')->label('Mahasiswa')->searchable()->sortable(),
                TextColumn::make('reportPeriod.week_number')->label('Minggu')->sortable(),
                TextColumn::make('latestVersion.submitted_at')->label('Dikirim')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('latestVersion.original_name')->label('Berkas')->limit(35),
            ])
            ->filters([
                SelectFilter::make('week_number')->label('Minggu')->options(fn (): array => WeeklyReport::query()
                    ->whereIn('weekly_reports.id', self::getEloquentQuery()->select('weekly_reports.id'))
                    ->join('report_periods', 'weekly_reports.report_period_id', '=', 'report_periods.id')
                    ->distinct()->orderBy('week_number')->pluck('week_number', 'week_number')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('reportPeriod', fn (Builder $periods): Builder => $periods->where('week_number', $data['value']))
                        : $query),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Detail laporan')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->schema([
                        TextEntry::make('reportPeriod.internship.student.user.name')->label('Mahasiswa'),
                        TextEntry::make('reportPeriod.week_number')->label('Minggu ke-'),
                        TextEntry::make('review_status')->label('Status')->formatStateUsing(fn (ReviewStatus $state): string => $state->label()),
                        TextEntry::make('latestVersion.original_name')->label('Berkas terbaru'),
                        TextEntry::make('latestVersion.submitted_at')->label('Dikirim')->dateTime('d M Y, H:i'),
                        TextEntry::make('latestVersion.student_note')->label('Catatan mahasiswa')->placeholder('—'),
                        TextEntry::make('latestVersion.id')->label('Baca dokumen')->formatStateUsing(fn ($state, WeeklyReport $record): string => match (true) {
                            $record->latestVersion === null => 'Tidak tersedia',
                            $record->latestVersion->mime_type === 'application/pdf' => 'Buka PDF di tab baru',
                            default => 'Unduh berkas untuk membacanya.',
                        })
                            ->url(fn ($state, WeeklyReport $record): ?string => $record->latestVersion?->mime_type === 'application/pdf' ? route('laporan.versi.preview', $record->latestVersion) : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('latestVersion.id')->label('Unduh berkas')->formatStateUsing(fn ($state, WeeklyReport $record): string => $record->latestVersion ? 'Unduh versi terbaru' : 'Tidak tersedia')
                            ->url(fn ($state, WeeklyReport $record): ?string => $record->latestVersion ? route('laporan.versi.download', $record->latestVersion) : null),
                    ])->modalHeading('Detail pemeriksaan laporan')->modalWidth('3xl')->modalSubmitAction(false)->modalCancelActionLabel('Tutup'),
                Action::make('decision')
                    ->label('Beri Keputusan')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->visible(fn (WeeklyReport $record): bool => $record->review_status === ReviewStatus::PendingReview)
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi keputusan pemeriksaan')
                    ->modalDescription('Pastikan keputusan sudah benar. Persetujuan mengunci laporan dan tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Konfirmasi keputusan')
                    ->schema([
                        Radio::make('decision')->label('Keputusan')->options([
                            ReportReviewDecision::Approved->value => 'Setujui',
                            ReportReviewDecision::RevisionRequested->value => 'Minta Revisi',
                        ])->required(),
                        Textarea::make('feedback')->label('Umpan balik')->rows(4)->maxLength(10000),
                    ])
                    ->action(function (WeeklyReport $record, array $data, ReviewReport $reviewReportAction): void {
                        Gate::authorize('review', $record);
                        $reviewReportAction->handle(auth()->user()->lecturer, $record, ReportReviewDecision::from($data['decision']), $data['feedback'] ?? null);
                    }),
                Action::make('assessment')
                    ->label('Simpan Draf Nilai')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (WeeklyReport $record): bool => $record->review_status === ReviewStatus::Approved && $record->assessment?->status !== AssessmentStatus::Published)
                    ->schema([
                        TextInput::make('score')->label('Nilai (0–100)')->numeric()->minValue(0)->maxValue(100)->step('0.01')->required(),
                        Textarea::make('notes')->label('Catatan')->rows(4)->maxLength(10000),
                    ])
                    ->fillForm(fn (WeeklyReport $record): array => [
                        'score' => $record->assessment?->score,
                        'notes' => $record->assessment?->notes,
                    ])
                    ->action(function (WeeklyReport $record, array $data, SaveAssessment $saveAssessmentAction): void {
                        Gate::authorize('assess', $record);
                        $saveAssessmentAction->handle(auth()->user()->lecturer, $record, $data['score'], $data['notes'] ?? null);
                    }),
                Action::make('publish')
                    ->label('Publikasikan')
                    ->color('success')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->requiresConfirmation()
                    ->visible(fn (WeeklyReport $record): bool => $record->assessment?->status === AssessmentStatus::Draft)
                    ->action(function (WeeklyReport $record, PublishAssessment $publishAssessmentAction): void {
                        $assessment = $record->assessment;
                        abort_unless($assessment instanceof Assessment, 404);
                        Gate::authorize('update', $assessment);
                        abort_unless($assessment->lecturer_id === auth()->user()->lecturer->id, 403);
                        $publishAssessmentAction->handle($assessment);
                    }),
            ])
            ->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $lecturer = auth()->user()?->lecturer;
        abort_unless($lecturer !== null, 403);

        return parent::getEloquentQuery()
            ->whereHas('reportPeriod.internship.student.supervisorAssignments', fn (Builder $query): Builder => $query
                ->where('lecturer_id', $lecturer->id)->where('status', 'active'))
            ->with(['reportPeriod.internship.student.user', 'latestVersion', 'assessment']);
    }

    public static function getPages(): array
    {
        return ['index' => ListReportReviews::route('/')];
    }
}
