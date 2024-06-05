(function () {
    const scriptTag = document.currentScript;
    const formId = scriptTag.getAttribute('data-form-id');
    const classes = scriptTag.getAttribute('data-classes') || '';
    const displayStyle = scriptTag.getAttribute('data-display') || 'bottom-right';
    const showFab = scriptTag.getAttribute('data-show-fab') !== 'false';
    const iconUrl = scriptTag.getAttribute('data-icon-url') || '';
    const iconSvg = scriptTag.getAttribute('data-icon-svg') || '';
    const popupWidth = scriptTag.getAttribute('data-popup-width') || '400px';
    const popupHeight = scriptTag.getAttribute('data-popup-height') || '600px';
    const popupBottom = scriptTag.getAttribute('data-popup-bottom') || '20px';
    const popupRight = scriptTag.getAttribute('data-popup-right') || '20px';
    const fabSize = scriptTag.getAttribute('data-fab-size') || '50px';
    const fabBottom = scriptTag.getAttribute('data-fab-bottom') || '20px';
    const fabRight = scriptTag.getAttribute('data-fab-right') || '20px';

    const formContainer = document.createElement('div');
    formContainer.id = `form-container-${formId}`;
    formContainer.className = `rounded ${classes}`;
    formContainer.style.display = 'none';
    formContainer.style.position = 'fixed';
    formContainer.style.zIndex = '999';
    formContainer.style.backgroundColor = 'white';
    formContainer.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.2)';
    formContainer.style.transition = 'transform 0.3s ease-in-out, opacity 0.3s ease-in-out';
    formContainer.style.transform = 'scale(0)';
    formContainer.style.opacity = '0';

    if (displayStyle === 'fullscreen') {
        formContainer.style.top = '0';
        formContainer.style.left = '0';
        formContainer.style.width = '100vw';
        formContainer.style.height = '100vh';
    } else if (displayStyle === 'bottom-right') {
        formContainer.style.bottom = popupBottom;
        formContainer.style.right = popupRight;
        formContainer.style.width = popupWidth;
        formContainer.style.height = popupHeight;
    }

    formContainer.innerHTML = `<iframe id="frame-${formId}" src="https://aliveforms.com/form/${formId}" frameborder="0" style="width: 100%; height: 100%;"></iframe>`;

    document.body.appendChild(formContainer);

    if (showFab) {
        const fabButton = document.createElement('button');
        fabButton.id = `fab-${formId}`;
        fabButton.style.position = 'fixed';
        fabButton.style.bottom = fabBottom;
        fabButton.style.right = fabRight;
        fabButton.style.width = fabSize;
        fabButton.style.height = fabSize;
        fabButton.style.borderRadius = '50%';
        fabButton.style.backgroundColor = '#007bff';
        fabButton.style.color = 'white';
        fabButton.style.border = 'none';
        fabButton.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        fabButton.style.fontSize = '24px';
        fabButton.style.cursor = 'pointer';
        fabButton.style.zIndex = '1000';
        fabButton.style.display = 'flex';
        fabButton.style.alignItems = 'center';
        fabButton.style.justifyContent = 'center';

        if (iconUrl) {
            const iconImage = document.createElement('img');
            iconImage.src = iconUrl;
            iconImage.style.width = '70%';
            iconImage.style.height = '70%';
            fabButton.appendChild(iconImage);
        } else if (iconSvg) {
            fabButton.innerHTML = iconSvg;
        } else {
            fabButton.innerHTML = '+';
        }

        document.body.appendChild(fabButton);

        fabButton.addEventListener('click', () => {
            if (formContainer.style.display === 'none' || formContainer.style.display === '') {
                showForm(formContainer.id);
            } else {
                hideForm(formContainer.id);
            }
        });
    }

    window.showForm = function (formId) {
        const formContainer = document.getElementById(formId);
        if (formContainer) {
            formContainer.style.display = 'block';
            setTimeout(() => {
                formContainer.style.transform = 'scale(1)';
                formContainer.style.opacity = '1';
            }, 10);
        }
    };

    window.hideForm = function (formId) {
        const formContainer = document.getElementById(formId);
        if (formContainer) {
            formContainer.style.transform = 'scale(0)';
            formContainer.style.opacity = '0';
            setTimeout(() => {
                formContainer.style.display = 'none';
            }, 300);
        }
    };

    window.getFrameRef = function (formId) {
        const iframe = document.getElementById(`frame-${formId}`);
        return iframe;
    };

    window.aliveFormIds = window.aliveFormIds || [];
    window.aliveFormIds.push(formContainer.id);
})();
