@props(['variant' => 'primary', 'type' => 'button', 'size' => 'md', 'tag' => 'button'])
@php($variants = ['primary'=>'bg-blue-600 text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700 focus:ring-blue-500','secondary'=>'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-400','danger'=>'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500','ghost'=>'text-slate-600 hover:bg-slate-100 focus:ring-slate-400'])
@php($sizes = ['sm'=>'px-3 py-2 text-xs','md'=>'px-4 py-2.5 text-sm','lg'=>'px-5 py-3 text-sm'])
@if($tag === 'a')
    <a {{ $attributes->merge(['class'=>'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 '.$variants[$variant].' '.$sizes[$size]]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class'=>'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 '.$variants[$variant].' '.$sizes[$size]]) }}>{{ $slot }}</button>
@endif
