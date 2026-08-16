<x-app-layout title="Emitir NF-e" header="Nova emissão" :partial="$partial ?? false">
    @include('nfe.partials.emissao-form', ['modalMode' => false])
</x-app-layout>
