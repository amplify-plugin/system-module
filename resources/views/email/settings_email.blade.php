@component('mail::message', ['width' => $width])
{!! $email_content !!}
@if($show_button)
@component('mail::button', ['url' => $button_url])
    {{ $button_text }}
@endcomponent
@endif
@endcomponent
