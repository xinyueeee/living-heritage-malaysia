import '../bootstrap';
import { supabase } from '../supabase';

document.addEventListener('DOMContentLoaded', async () => {
    const statusEl = document.querySelector('[data-auth-status]');

    const setStatus = (text) => {
        if (statusEl) {
            statusEl.textContent = text;
        }
    };

    const bounceToLogin = () => {
        setTimeout(() => window.location.replace('/login'), 1500);
    };

    const { data, error } = await supabase.auth.getSession();

    if (error || !data.session) {
        setStatus('We could not sign you in. Redirecting to login…');
        bounceToLogin();
        return;
    }

    try {
        const response = await window.axios.post('/auth/sync', {
            access_token: data.session.access_token,
        });

        window.location.replace(response.data.redirect || '/');
    } catch (e) {
        setStatus('Something went wrong. Redirecting to login…');
        bounceToLogin();
    }
});
