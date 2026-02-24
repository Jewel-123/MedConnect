/**
 * Custom Stylish Modal System
 * Overrides window.alert, window.confirm, and window.prompt
 */

const CustomModal = {
    overlay: null,
    container: null,
    resolve: null,

    init() {
        if (this.overlay) return;

        // Create modal HTML structure
        const html = `
            <div class="custom-modal-overlay" id="customModalOverlay">
                <div class="custom-modal-container">
                    <div class="custom-modal-icon" id="customModalIcon">
                        <i class="ph ph-info"></i>
                    </div>
                    <div class="custom-modal-title" id="customModalTitle">Notification</div>
                    <div class="custom-modal-message" id="customModalMessage">Message goes here...</div>
                    <div class="custom-modal-input-container" id="customModalInputContainer" style="display: none;">
                        <input type="text" class="custom-modal-input" id="customModalInput" autocomplete="off">
                    </div>
                    <div class="custom-modal-footer">
                        <button class="custom-modal-btn custom-modal-btn-cancel" id="customModalCancel" style="display: none;">Cancel</button>
                        <button class="custom-modal-btn custom-modal-btn-confirm" id="customModalConfirm">OK</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
        this.overlay = document.getElementById('customModalOverlay');
        this.container = this.overlay.querySelector('.custom-modal-container');

        // Event Listeners
        document.getElementById('customModalConfirm').addEventListener('click', () => this.handleAction(true));
        document.getElementById('customModalCancel').addEventListener('click', () => this.handleAction(false));
        
        // Keyboard Support
        window.addEventListener('keydown', (e) => {
            if (!this.overlay.classList.contains('active')) return;
            
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleAction(true);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.handleAction(false);
            }
        });
    },

    show(type, message, defaultValue = '') {
        this.init();
        
        const titleEl = document.getElementById('customModalTitle');
        const messageEl = document.getElementById('customModalMessage');
        const iconEl = document.getElementById('customModalIcon');
        const inputContainer = document.getElementById('customModalInputContainer');
        const inputEl = document.getElementById('customModalInput');
        const cancelBtn = document.getElementById('customModalCancel');
        const confirmBtn = document.getElementById('customModalConfirm');

        // Reset
        inputContainer.style.display = 'none';
        cancelBtn.style.display = 'none';
        confirmBtn.textContent = 'OK';
        
        messageEl.textContent = message;

        switch (type) {
            case 'alert':
                titleEl.textContent = 'Notification';
                iconEl.innerHTML = '<i class="ph ph-info"></i>';
                confirmBtn.textContent = 'Got it';
                break;
            case 'confirm':
                titleEl.textContent = 'Please Confirm';
                iconEl.innerHTML = '<i class="ph ph-question"></i>';
                cancelBtn.style.display = 'block';
                confirmBtn.textContent = 'Yes, Proceed';
                break;
            case 'prompt':
                titleEl.textContent = 'Input Required';
                iconEl.innerHTML = '<i class="ph ph-keyboard"></i>';
                inputContainer.style.display = 'block';
                inputEl.value = defaultValue;
                cancelBtn.style.display = 'block';
                confirmBtn.textContent = 'Submit';
                setTimeout(() => inputEl.focus(), 100);
                break;
        }

        this.overlay.classList.add('active');
        
        return new Promise((resolve) => {
            this.resolve = resolve;
        });
    },

    handleAction(status) {
        if (!this.overlay.classList.contains('active')) return;

        const inputEl = document.getElementById('customModalInput');
        const isPrompt = document.getElementById('customModalInputContainer').style.display !== 'none';
        
        let result;
        if (isPrompt) {
            result = status ? inputEl.value : null;
        } else {
            result = status;
        }

        this.overlay.classList.remove('active');
        
        if (this.resolve) {
            this.resolve(result);
            this.resolve = null;
        }
    }
};

// Override native functions
window.alert = function(message) {
    return CustomModal.show('alert', message);
};

window.confirm = function(message) {
    return CustomModal.show('confirm', message);
};

window.prompt = function(message, defaultValue = '') {
    return CustomModal.show('prompt', message, defaultValue);
};

console.log('Custom Stylish Modals Loaded Successfully');
