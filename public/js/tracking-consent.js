/* Minimal first-party consent choice for optional analytics and advertising measurement. */
(() => {
    'use strict';

    const panel = document.getElementById('livebajar-consent');
    if (!panel) return;
    if (panel.dataset.enabled !== '1') {
        panel.hidden = true;
        return;
    }

    const getChoice = () => {
        const entry = document.cookie.split('; ').find((item) => item.startsWith('livebajar_tracking_consent='));
        return entry ? decodeURIComponent(entry.slice('livebajar_tracking_consent='.length)) : null;
    };

    const publish = (choice) => {
        window.LiveBajarConsent = Object.freeze({analytics: choice === 'granted', advertising: choice === 'granted'});
        window.dispatchEvent(new CustomEvent('livebajar:consent-changed', {detail: {consent: choice}}));
    };

    const show = () => { panel.hidden = false; };
    const hide = () => { panel.hidden = true; };
    const setChoice = (choice) => {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `livebajar_tracking_consent=${choice}; Max-Age=15552000; Path=/; SameSite=Lax${secure}`;
        hide();
        publish(choice);
        if (choice === 'denied') {
            document.cookie.split(';').forEach((entry) => {
                const name = entry.trim().split('=')[0];
                if (/^(?:_ga(?:_|$)|_gcl_|_fbp$|_fbc$|_ttp$|tt_)/i.test(name)) {
                    document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax${secure}`;
                }
            });
            window.setTimeout(() => window.location.reload(), 100);
        }
    };

    panel.querySelectorAll('[data-consent-choice]').forEach((button) => {
        button.addEventListener('click', () => setChoice(button.dataset.consentChoice === 'granted' ? 'granted' : 'denied'));
    });
    document.querySelectorAll('[data-open-consent]').forEach((button) => button.addEventListener('click', show));

    const choice = getChoice();
    if (choice === 'granted' || choice === 'denied') {
        publish(choice);
    } else {
        show();
    }
})();
