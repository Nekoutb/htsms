@php($tone = $tone ?? 'default')
{{-- HTSMS mark: a message tile whose ascending signal bars double as a "send" motion.
     tone="light" for use on the dark sidebar/marketing ink; default for light surfaces. --}}
<span class="logo logo--{{ $tone }} {{ $class ?? '' }}" aria-hidden="true">
<svg class="logo__mark" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
<rect x="1.5" y="1.5" width="29" height="29" rx="8" class="logo__tile"/>
<path d="M9 21.5h2.4v-4.2H9v4.2Zm4.8 0h2.4v-8.4h-2.4v8.4Zm4.8 0h2.4V9h-2.4v12.5Z" class="logo__bars"/>
<circle cx="22.4" cy="10.2" r="2.1" class="logo__ping"/>
</svg>
<span class="logo__word">HTSMS</span>
</span>
