@vite(['resources/css/components/tombol-whatsapp.css'])

<a href="https://wa.me/{{ config('salon.whatsapp') }}?text={{ rawurlencode(config('salon.whatsapp_pesan_awal')) }}"
   class="wa-mengambang"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="Hubungi Salon Rengganis lewat WhatsApp">
    <span class="wa-ikon" aria-hidden="true">💬</span>
    <span class="wa-teks">Tanya via WhatsApp</span>
</a>
