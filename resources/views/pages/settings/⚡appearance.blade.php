<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Pengaturan tampilan')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">Pengaturan tampilan</flux:heading>

    <x-pages::settings.layout heading="Tampilan" subheading="Pilih tema yang nyaman untuk akun Anda.">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">Terang</flux:radio>
            <flux:radio value="dark" icon="moon">Gelap</flux:radio>
            <flux:radio value="system" icon="computer-desktop">Sistem</flux:radio>
        </flux:radio.group>
    </x-pages::settings.layout>
</section>
