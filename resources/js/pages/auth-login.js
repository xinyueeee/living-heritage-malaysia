import '../bootstrap';
import { supabase } from '../supabase';

document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('[data-google-login]');
    const errorBox = document.querySelector('[data-auth-error]');

    if (!buttons.length) {
        return;
    }

    const signInWithGoogle = async (triggerButton) => {
        buttons.forEach((button) => {
            button.disabled = true;
        });

        if (errorBox) {
            errorBox.hidden = true;
        }

        const originalLabel = triggerButton.querySelector('[data-label]');
        if (originalLabel) {
            originalLabel.textContent = 'Redirecting to Google…';
        }

        const { error } = await supabase.auth.signInWithOAuth({
            provider: 'google',
            options: {
                redirectTo: `${window.location.origin}/auth/callback`,
                queryParams: {
                    prompt: 'select_account',
                },
            },
        });

        if (error) {
            buttons.forEach((button) => {
                button.disabled = false;
            });

            if (originalLabel) {
                originalLabel.textContent = 'Continue with Google';
            }

            if (errorBox) {
                errorBox.textContent = error.message;
                errorBox.hidden = false;
            }
        }
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => signInWithGoogle(button));
    });
});
