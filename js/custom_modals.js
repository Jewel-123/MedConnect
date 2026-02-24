/**
 * Custom Stylish Modals for MedConnect
 * Replaces native alert(), confirm(), and prompt() with a themed UI.
 */

(function () {
    // Inject Styles
    const style = document.createElement('style');
    style.textContent = `
        .cm-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .cm-overlay.cm-open { opacity: 1; }
        
        .cm-modal {
            background: #ffffff;
            width: 90%;
            max-width: 400px;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .cm-overlay.cm-open .cm-modal { transform: scale(1); }
        
        .cm-header {
            padding: 1.5rem 1.5rem 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .cm-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .cm-icon-alert { background: #e0f2fe; color: #0ea5e9; }
        .cm-icon-confirm { background: #fef3c7; color: #f59e0b; }
        .cm-icon-prompt { background: #dcfce7; color: #10b981; }
        
        .cm-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #0f172a;
        }
        
        .cm-body {
            padding: 1rem 1.5rem 1.5rem;
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .cm-input-wrapper {
            margin-top: 1rem;
        }
        .cm-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .cm-input:focus { border-color: #0ea5e9; }
        
        .cm-footer {
            padding: 1.25rem 1.5rem;
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        .cm-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .cm-btn-secondary {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #64748b;
        }
        .cm-btn-secondary:hover { background: #f1f5f9; }
        
        .cm-btn-primary {
            background: #0ea5e9;
            color: #ffffff;
        }
        .cm-btn-primary:hover { background: #0284c7; }
    `;
    document.head.appendChild(style);

    // Modal Manager
    const CustomModal = {
        queue: [],
        active: null,

        create(type, message, defaultValue = '') {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.className = 'cm-overlay';

                let iconClass = 'cm-icon-alert';
                let icon = 'ph-info';
                let title = 'Notification';

                if (type === 'confirm') {
                    iconClass = 'cm-icon-confirm';
                    icon = 'ph-question';
                    title = 'Confirm Action';
                } else if (type === 'prompt') {
                    iconClass = 'cm-icon-prompt';
                    icon = 'ph-pencil-line';
                    title = 'Input Required';
                }

                overlay.innerHTML = `
                    <div class="cm-modal">
                        <div class="cm-header">
                            <div class="cm-icon ${iconClass}">
                                <i class="ph ${icon}"></i>
                            </div>
                            <div class="cm-title">${title}</div>
                        </div>
                        <div class="cm-body">
                            <div>${message}</div>
                            ${type === 'prompt' ? `
                                <div class="cm-input-wrapper">
                                    <input type="text" class="cm-input" value="${defaultValue}" id="cm-prompt-input">
                                </div>
                            ` : ''}
                        </div>
                        <div class="cm-footer">
                            ${type !== 'alert' ? `<button class="cm-btn cm-btn-secondary" id="cm-cancel">Cancel</button>` : ''}
                            <button class="cm-btn cm-btn-primary" id="cm-ok">OK</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(overlay);

                // Trigger animation
                setTimeout(() => overlay.classList.add('cm-open'), 10);

                if (type === 'prompt') {
                    const input = overlay.querySelector('#cm-prompt-input');
                    input.focus();
                    input.select();
                    input.addEventListener('keyup', (e) => {
                        if (e.key === 'Enter') overlay.querySelector('#cm-ok').click();
                        if (e.key === 'Escape') overlay.querySelector('#cm-cancel')?.click();
                    });
                }

                const close = (result) => {
                    overlay.classList.remove('cm-open');
                    setTimeout(() => {
                        document.body.removeChild(overlay);
                        resolve(result);
                    }, 300);
                };

                overlay.querySelector('#cm-ok').onclick = () => {
                    if (type === 'prompt') {
                        close(overlay.querySelector('#cm-prompt-input').value);
                    } else {
                        close(true);
                    }
                };

                if (type !== 'alert') {
                    overlay.querySelector('#cm-cancel').onclick = () => close(type === 'confirm' ? false : null);
                }
            });
        }
    };

    // Override Globals
    // Note: To truly mimic the SYNCHRONOUS behavior of native popups, we would need to stop execution.
    // Since custom HTML modals CANNOT block the JS thread, these become async.
    // However, for systems that don't rely on the immediate return value (like alert), it's a seamless swap.
    // For confirm/prompt, users SHOULD use 'await confirm()' if they want to wait for the user.

    window.alert = function (message) {
        CustomModal.create('alert', message);
    };

    const nativeConfirm = window.confirm;
    window.confirm = function (message) {
        // Warning: This implementation returns a Promise. 
        // If the calling code isn't async/await, it will receive the Promise object (which is truthy).
        // We keep the nativeRef just in case, but swap for styling.
        return CustomModal.create('confirm', message);
    };

    window.prompt = function (message, defaultValue = '') {
        return CustomModal.create('prompt', message, defaultValue);
    };

})();
