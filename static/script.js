document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('file-input');
    const fileNameSpan = document.getElementById('file-name');
    const openModalBtn = document.getElementById('open-modal-btn');
    const statusMessage = document.getElementById('status-message');
    
    const encryptModal = document.getElementById('encrypt-modal');
    const decryptModal = document.getElementById('decrypt-modal');
    const closeModalBtns = document.querySelectorAll('.close-btn');

    const encryptPasswordInput = document.getElementById('encrypt-password');
    const generateKeyBtn = document.getElementById('generate-key-btn');
    const downloadKeyBtn = document.getElementById('download-key-btn');
    const encryptFilenameInput = document.getElementById('encrypt-filename');
    const startEncryptBtn = document.getElementById('start-encrypt-btn');

    const decryptPasswordInput = document.getElementById('decrypt-password');
    const uploadKeyInput = document.getElementById('upload-key-input');
    const startDecryptBtn = document.getElementById('start-decrypt-btn');

    const successOverlay = document.getElementById('success-animation-overlay');

    let currentFile = null;
    let progressInterval = null;

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            currentFile = fileInput.files[0];
            fileNameSpan.textContent = currentFile.name;
            openModalBtn.disabled = false;
        } else {
            currentFile = null;
            fileNameSpan.textContent = '未选择文件';
            openModalBtn.disabled = true;
        }
    });
    
    openModalBtn.addEventListener('click', () => {
        if (!currentFile) {
            return;
        }
        if (currentFile.name.toLowerCase().endsWith('.kz')) {
            decryptModal.style.display = 'block';
        } else {
            encryptModal.style.display = 'block';
            encryptFilenameInput.value = currentFile.name.split('.').slice(0, -1).join('.');
        }
    });

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            encryptModal.style.display = 'none';
            decryptModal.style.display = 'none';
            resetProgressBars();
            clearInterval(progressInterval);
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target == encryptModal) {
            encryptModal.style.display = 'none';
            resetProgressBars();
            clearInterval(progressInterval);
        }
        if (event.target == decryptModal) {
            decryptModal.style.display = 'none';
            resetProgressBars();
            clearInterval(progressInterval);
        }
    });

    encryptPasswordInput.addEventListener('input', () => {
        downloadKeyBtn.disabled = !encryptPasswordInput.value;
    });

    generateKeyBtn.addEventListener('click', async () => {
        try {
            const response = await fetch('/generate_key', { method: 'POST' });
            const data = await response.json();
            if (data.success) {
                encryptPasswordInput.value = data.key;
                downloadKeyBtn.disabled = false;
            } else {
                showMessage('生成密钥失败', 'error');
            }
        } catch (error) {
            showMessage('生成密钥失败', 'error');
        }
    });

    downloadKeyBtn.addEventListener('click', () => {
        const key = encryptPasswordInput.value;
        if (!key) {
            showMessage('请先生成或输入密钥', 'error');
            return;
        }
        const blob = new Blob([key], { type: 'text/plain' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'encryption_key.txt';
        a.click();
        URL.revokeObjectURL(a.href);
    });
    
    startEncryptBtn.addEventListener('click', () => {
        const password = encryptPasswordInput.value;
        const newFilename = encryptFilenameInput.value;
        if (!password) {
            showMessage('请输入密钥', 'error');
            return;
        }
        
        simulateProgressAndHandleAction(
            '/process_file', 
            { action: 'encrypt', password: password, new_filename: newFilename }, 
            document.getElementById('encrypt-progress')
        );
    });

    uploadKeyInput.addEventListener('change', () => {
        const file = uploadKeyInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                decryptPasswordInput.value = e.target.result;
            };
            reader.readAsText(file);
        }
    });
    
    startDecryptBtn.addEventListener('click', () => {
        const password = decryptPasswordInput.value;
        if (!password) {
            showMessage('请输入密钥', 'error');
            return;
        }
        
        simulateProgressAndHandleAction(
            '/process_file', 
            { action: 'decrypt', password: password }, 
            document.getElementById('decrypt-progress')
        );
    });

    function resetProgressBars() {
        const bars = document.querySelectorAll('.progress-bar');
        bars.forEach(bar => {
            bar.style.width = '0%';
        });
    }

    function simulateProgressAndHandleAction(endpoint, formDataParams, progressBar) {
        let width = 0;
        // 用来控制用户的 想让用户等得久就改大想快就小QWQ
        const totalDuration = 10000;
        const step = 50;
        const steps = totalDuration / step;
        const increment = 100 / steps;

        progressInterval = setInterval(() => {
            if (width >= 100) {
                clearInterval(progressInterval);
                handleAction(endpoint, formDataParams);
            } else {
                width += increment + (Math.random() * 2);
                if (width > 100) width = 100;
                progressBar.style.width = width + '%';
            }
        }, step);
    }
    
    async function handleAction(endpoint, formDataParams) {
        const formData = new FormData();
        formData.append('file', currentFile);
        for (const key in formDataParams) {
            formData.append(key, formDataParams[key]);
        }
        
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (progressInterval) {
                clearInterval(progressInterval);
                const progressBar = formDataParams.action === 'encrypt' ? document.getElementById('encrypt-progress') : document.getElementById('decrypt-progress');
                progressBar.style.width = '100%';
            }

            if (data.success) {
                encryptModal.style.display = 'none';
                decryptModal.style.display = 'none';
                
                successOverlay.classList.add('visible');
                
                setTimeout(() => {
                    successOverlay.classList.remove('visible');
                    resetProgressBars();
                    if (data.download_url) {
                        const downloadLink = document.createElement('a');
                        downloadLink.href = data.download_url;
                        downloadLink.download = data.download_url.split('/').pop();
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                }, 2500);
            } else {
                setTimeout(() => {
                    encryptModal.style.display = 'none';
                    decryptModal.style.display = 'none';
                    resetProgressBars();
                    showMessage(data.message, 'error');
                }, 500);
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('处理失败，请检查服务器连接。', 'error');
            resetProgressBars();
            clearInterval(progressInterval);
        }
    }

    function showMessage(message, type) {
        statusMessage.textContent = message;
        statusMessage.className = `status-message ${type}`;
        statusMessage.style.visibility = 'visible';
        statusMessage.style.opacity = '1';
    }
});
