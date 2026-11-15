const PATH_IKON = {
    cek: '<circle cx="12" cy="12" r="9"/><path d="M8.5 12.2l2.4 2.4 4.8-5.1"/>',
    peringatan: '<path d="M10.3 4.3L2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    tandaCek: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
    tandaSilang: '<path d="M6 6l12 12M18 6L6 18"/>',
    edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>',
    catatan: '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2.8h6V4M8.5 10h7M8.5 14h7M8.5 18h4"/>',
    gembok: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    mata: '<path d="M2 12s3.8-7 10-7 10 7 10 7-3.8 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
    mataTutup: '<path d="M17.9 17.9A10.1 10.1 0 0 1 12 19c-6.2 0-10-7-10-7a18.2 18.2 0 0 1 5.1-5.9"/><path d="M9.9 5.2A9.8 9.8 0 0 1 12 5c6.2 0 10 7 10 7a18.3 18.3 0 0 1-2.5 3.4"/><path d="M3 3l18 18"/>',
    roda: '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M2 12h3M19 12h3M4.9 19.1L7 17M17 7l2.1-2.1"/>',
};

export function svgIkon(nama, kelas = 'ikon-ui') {
    const isi = PATH_IKON[nama] || PATH_IKON.cek;
    return `<svg class="${kelas}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${isi}</svg>`;
}
