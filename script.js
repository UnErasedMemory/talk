document.addEventListener('DOMContentLoaded', () => {
    const micBtn = document.getElementById('mic-btn');
    const screenBtn = document.getElementById('screen-btn');
    const joinVoice = document.getElementById('join-voice');
    
    const mediaContainer = document.getElementById('media-container');
    const localVideo = document.getElementById('local-video');
    const stopMediaBtn = document.getElementById('stop-media');
    
    const toast = document.getElementById('permission-toast');
    const toastMessage = document.getElementById('toast-message');

    // Dashboard Elements
    const btnGiveAccess = document.getElementById('btn-give-access');
    const dashboardView = document.getElementById('dashboard-access-view');
    const chatMessages = document.getElementById('chat-messages');
    const chatInputArea = document.getElementById('chat-input-area');

    let audioStream = null;
    let screenStream = null;
    let hasGrantedBasePermission = false;

    function showToast(msg, duration = 3000) {
        toastMessage.textContent = msg;
        toast.classList.remove('hidden');
        if(duration > 0) {
            setTimeout(() => {
                toast.classList.add('hidden');
            }, duration);
        }
    }

    // Modal dialog to ask for permissions, mimicking the HRIS folder's SweetAlert style
    function showPermissionDialog() {
        return Swal.fire({
            title: '🎙️ Izin Akses Komunikasi',
            html: '<p style="font-size: 14px;">Aplikasi membutuhkan akses berikut untuk berfungsi:</p>' +
                  '<ul style="font-size: 14px; text-align: left; margin-top: 8px;">' +
                  '<li>✓ <b>Microphone:</b> Untuk berbicara di Voice Channel</li>' +
                  '<li>✓ <b>Screen Share:</b> Untuk membagikan layar (opsional)</li>' +
                  '</ul>',
            iconHtml: '<img src="logo.png" style="width:60px; height:60px; border-radius:12px;">',
            customClass: {
                icon: 'no-border'
            },
            showCancelButton: true,
            confirmButtonText: 'Izinkan Akses',
            cancelButtonText: 'Nanti Saja',
            confirmButtonColor: '#5865F2', // Discord brand color
            cancelButtonColor: '#72767d',
            reverseButtons: false,
        });
    }

    async function handleGiveAccess() {
        const result = await showPermissionDialog();
        
        if (result.isConfirmed) {
            hasGrantedBasePermission = true;
            
            // Trigger microphone request directly after SweetAlert confirm
            try {
                showToast("Meminta izin microphone...", 0);
                audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                micBtn.classList.add('recording');
                showToast("Izin microphone diberikan!");
                
                // Switch UI from Dashboard to Chat
                dashboardView.style.display = 'none';
                chatMessages.style.display = 'block';
                chatInputArea.style.display = 'block';
                
            } catch (error) {
                console.error("Microphone access denied:", error);
                Swal.fire({
                    title: '❌ Akses Ditolak',
                    html: '<p style="font-size: 14px;">Akses microphone ditolak. Silakan izinkan di pengaturan browser Anda.</p>',
                    icon: 'error',
                    confirmButtonColor: '#5865F2',
                });
                showToast("Izin microphone ditolak.");
            }
        }
    }

    async function requestMicrophone() {
        if (!hasGrantedBasePermission) {
            return handleGiveAccess();
        }

        if (audioStream) {
            // Stop mic
            audioStream.getTracks().forEach(track => track.stop());
            audioStream = null;
            micBtn.classList.remove('recording');
            showToast("Microphone dinonaktifkan");
            return;
        }

        try {
            showToast("Meminta izin microphone...", 0);
            audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            micBtn.classList.add('recording');
            showToast("Izin microphone diberikan!");
        } catch (error) {
            console.error("Microphone access denied:", error);
            showToast("Izin microphone ditolak.");
        }
    }

    async function requestScreenShare() {
        if (!hasGrantedBasePermission) {
            Swal.fire('Perhatian', 'Silakan klik "Berikan Akses" di dashboard terlebih dahulu.', 'warning');
            return;
        }

        if (screenStream) {
            // Stop screen
            stopScreenShare();
            return;
        }

        try {
            showToast("Meminta izin screen share...", 0);
            screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
            screenBtn.classList.add('active');
            
            mediaContainer.style.display = 'block';
            localVideo.srcObject = screenStream;
            
            showToast("Screen share dimulai!");

            // Handle browser built-in stop button
            screenStream.getVideoTracks()[0].onended = () => {
                stopScreenShare();
            };
        } catch (error) {
            console.error("Screen share access denied:", error);
            showToast("Izin screen share ditolak.");
        }
    }

    function stopScreenShare() {
        if (screenStream) {
            screenStream.getTracks().forEach(track => track.stop());
            screenStream = null;
        }
        screenBtn.classList.remove('active');
        mediaContainer.style.display = 'none';
        localVideo.srcObject = null;
        showToast("Screen share dihentikan");
    }

    // Custom SweetAlert Icon Styling
    const style = document.createElement('style');
    style.innerHTML = `
        .swal2-icon.no-border {
            border: none !important;
            margin: 1.5em auto .6em !important;
        }
    `;
    document.head.appendChild(style);

    // Event Listeners
    btnGiveAccess.addEventListener('click', handleGiveAccess);
    micBtn.addEventListener('click', requestMicrophone);
    screenBtn.addEventListener('click', requestScreenShare);
    joinVoice.addEventListener('click', requestMicrophone);
    stopMediaBtn.addEventListener('click', stopScreenShare);
});
