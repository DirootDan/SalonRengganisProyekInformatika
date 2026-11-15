import { ikatSemuaModalAksesibel, ikatSidebarStaf, tampilkanToastGlobal } from '../components/staf-ui';

document.addEventListener('DOMContentLoaded', () => {
    ikatSidebarStaf();
    window.tampilkanToast = tampilkanToastGlobal;
    ikatSemuaModalAksesibel();
});
