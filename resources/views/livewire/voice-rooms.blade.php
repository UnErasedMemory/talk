<div class="flex h-[calc(100vh-4rem)] bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800" x-data="webrtc()">
    <!-- Sidebar / Room List -->
    <div class="w-64 border-r border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
            <h2 class="font-semibold text-gray-800 dark:text-gray-200">Voice Rooms</h2>
        </div>
        <div class="flex-1 overflow-y-auto p-2 space-y-1">
            @foreach($rooms as $room)
                <button @click="if($wire.hasGrantedAccess) { $wire.selectRoom({{ $room->id }}) } else { requestAccess() }" 
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-left transition-colors {{ $selectedRoomId === $room->id ? 'bg-primary-100 text-primary-900 dark:bg-primary-900/30 dark:text-primary-100' : 'text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700/50' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    <span class="font-medium truncate">{{ $room->name }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col bg-white dark:bg-gray-900">
        @if($selectedRoomId)
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-white dark:bg-gray-900">
                <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200 flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></div>
                    Connected to {{ $rooms->firstWhere('id', $selectedRoomId)->name }}
                </h3>
                <!-- Buttons moved to the local user card -->
            </div>
            
            <div class="flex-1 p-6 overflow-y-auto bg-gray-50/50 dark:bg-gray-900/50">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="participants-grid">
                    <!-- Local User -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center justify-center aspect-square relative overflow-hidden">
                        
                        <!-- Pulse Ring Indicator -->
                        <div class="absolute inset-0 m-auto w-16 h-16 rounded-full bg-green-500 opacity-30 pointer-events-none transition-transform duration-75 ease-out"
                             :style="`transform: scale(${1 + (localVolume / 25)});`">
                        </div>

                        <div class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 flex items-center justify-center text-xl font-bold mb-3 z-20 relative">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <span class="font-medium text-gray-700 dark:text-gray-300 text-sm z-20 mb-3">You</span>
                        
                        <div class="flex items-center gap-2 z-20 mt-auto">
                            <!-- Mic Button -->
                            <button @click="toggleMic" :class="micEnabled ? 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'" class="p-2 rounded-full transition-colors" title="Toggle Microphone">
                                <svg x-show="micEnabled" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                                <svg x-show="!micEnabled" style="display:none;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" /></svg>
                            </button>
                            <!-- Share Screen Button -->
                            <button @click="toggleScreenShare" :class="screenSharing ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200'" class="p-2 rounded-full transition-colors" title="Toggle Screen Share">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </button>
                        </div>
                        
                        <!-- Local Screen Share Preview -->
                        <video id="localScreen" x-show="screenSharing" class="absolute inset-0 w-full h-full object-cover z-10 bg-black" autoplay muted playsinline></video>
                    </div>

                    <!-- Remote Users will be appended here by JS -->
                </div>
            </div>
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-gray-400 dark:text-gray-600">
                @if($hasGrantedAccess)
                    <div class="flex flex-col items-center">
                        <svg class="w-16 h-16 mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </svg>
                        <p class="text-lg">Select a room to join the conversation</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center p-8 bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 max-w-md w-full mx-4 text-center transform transition-all hover:scale-[1.02]">
                        <div class="bg-indigo-50 dark:bg-indigo-900/30 p-4 rounded-full mb-6">
                            <img src="{{ asset('logo.png') }}" alt="Logo" style="width: 90px; height: 90px; object-fit: contain;" class="rounded-2xl drop-shadow-md">
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-3">Selamat Datang!</h2>
                        <p class="text-gray-500 dark:text-gray-400 mb-8 text-sm leading-relaxed">
                            Aplikasi Voice Rooms membutuhkan izin akses ke <b>Microphone</b> (dan opsional Screen Share) agar kamu bisa berinteraksi dengan rekan kerja.
                        </p>
                        <button @click="requestAccess" style="background: linear-gradient(to right, #6366f1, #9333ea); color: white;" class="w-full py-3.5 px-6 font-semibold rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 hover:opacity-90">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                            Berikan Akses Sekarang
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @script
    <script>
        Alpine.data('webrtc', () => ({
            currentRoomId: null,
            channel: null,
            localStream: null,
            localScreenStream: null,
            peers: {},
            micEnabled: true,
            screenSharing: false,
            localVolume: 0,
            audioContext: null,
            analyser: null,
            microphone: null,
            animationFrame: null,
            usersInRoom: {},
            iceServers: {
                iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
            },

                async requestAccess() {
                    if (typeof Swal === 'undefined') {
                        // Dynamically load SweetAlert if not present
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                        document.head.appendChild(script);
                        await new Promise(r => script.onload = r);
                        
                        const style = document.createElement('style');
                        style.innerHTML = '.swal2-icon.border-0 { border: none !important; }';
                        document.head.appendChild(style);
                    }

                    const result = await Swal.fire({
                        title: '🎙️ Izin Akses Komunikasi',
                        html: '<p class="text-sm">Aplikasi membutuhkan akses berikut untuk berfungsi:</p>' +
                              '<ul class="text-sm text-left mt-2">' +
                              '<li>✓ <b>Microphone:</b> Untuk berbicara di Voice Channel</li>' +
                              '<li>✓ <b>Screen Share:</b> Untuk membagikan layar (opsional)</li>' +
                              '</ul>',
                        iconHtml: '<img src="/logo.png" style="width:60px; height:60px; border-radius:12px;">',
                        customClass: {
                            icon: 'border-0'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Izinkan Akses',
                        cancelButtonText: 'Nanti Saja',
                        confirmButtonColor: '#4f46e5',
                        cancelButtonColor: '#6b7280',
                    });

                    if (result.isConfirmed) {
                        try {
                            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                            stream.getTracks().forEach(t => t.stop());
                            
                            $wire.grantAccess();
                            
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Izin akses diberikan. Silakan pilih room di sidebar.',
                                icon: 'success',
                                confirmButtonColor: '#4f46e5',
                            });
                        } catch (e) {
                            Swal.fire('Akses Ditolak', 'Izin microphone ditolak atau tidak tersedia.', 'error');
                        }
                    }
                },

                init() {
                    Livewire.on('room-selected', ({ roomId }) => {
                        this.joinRoom(roomId);
                    });
                },

                setupAudioVisualizer() {
                    if (!this.localStream || !this.localStream.getAudioTracks().length) return;
                    
                    if (!this.audioContext) {
                        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    }
                    if (this.audioContext.state === 'suspended') {
                        this.audioContext.resume();
                    }
                    
                    if (this.microphone) {
                        this.microphone.disconnect();
                    }
                    
                    this.analyser = this.audioContext.createAnalyser();
                    this.analyser.fftSize = 256;
                    this.microphone = this.audioContext.createMediaStreamSource(this.localStream);
                    this.microphone.connect(this.analyser);
                    
                    const bufferLength = this.analyser.frequencyBinCount;
                    const dataArray = new Uint8Array(bufferLength);
                    
                    const updateVolume = () => {
                        if (!this.micEnabled || !this.localStream) {
                            this.localVolume = 0;
                            this.animationFrame = requestAnimationFrame(updateVolume);
                            return;
                        }
                        
                        this.analyser.getByteFrequencyData(dataArray);
                        let sum = 0;
                        for(let i = 0; i < bufferLength; i++) {
                            sum += dataArray[i];
                        }
                        let average = sum / bufferLength;
                        
                        // Map average to 0-100 scale approximately
                        this.localVolume = (average / 255) * 100;
                        
                        this.animationFrame = requestAnimationFrame(updateVolume);
                    };
                    
                    if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
                    updateVolume();
                },

                async joinRoom(roomId) {
                    if (this.currentRoomId === roomId) return;
                    
                    this.leaveRoom();
                    this.currentRoomId = roomId;

                    try {
                        // Request ONLY microphone initially, but start muted
                        this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                        this.micEnabled = false; // Start muted
                        this.localStream.getAudioTracks().forEach(track => {
                            track.enabled = false;
                        });
                        this.setupAudioVisualizer();
                    } catch (e) {
                        console.error("Microphone access denied:", e);
                        // Proceed without mic if denied, user can enable it later
                        this.micEnabled = false;
                    }

                    this.channel = window.Echo.join(`room.${roomId}`);

                    this.channel.here((users) => {
                        users.forEach(user => {
                            this.usersInRoom[user.id] = user;
                        });
                    })
                    .joining((user) => {
                        this.usersInRoom[user.id] = user;
                        this.createPeerConnection(user.id, true);
                        this.updateRemoteUserName(user.id);
                    })
                    .leaving((user) => {
                        delete this.usersInRoom[user.id];
                        this.removePeer(user.id);
                    })
                    .listenForWhisper('webrtc-signal', (data) => {
                        this.handleSignalingData(data);
                    });
                },

                updateRemoteUserName(userId) {
                    let el = document.getElementById(`peer-${userId}`);
                    if (el && this.usersInRoom[userId]) {
                        const userName = this.usersInRoom[userId].name;
                        const initials = userName.charAt(0).toUpperCase();
                        
                        const nameSpan = el.querySelector('.user-name');
                        if (nameSpan) nameSpan.textContent = userName;
                        
                        const initialSpan = el.querySelector('.user-initials');
                        if (initialSpan) initialSpan.textContent = initials;
                    }
                },

                leaveRoom() {
                    if (this.channel) {
                        window.Echo.leave(`room.${this.currentRoomId}`);
                        this.channel = null;
                    }
                    if (this.localStream) {
                        this.localStream.getTracks().forEach(track => track.stop());
                        this.localStream = null;
                    }
                    if (this.localScreenStream) {
                        this.localScreenStream.getTracks().forEach(track => track.stop());
                        this.localScreenStream = null;
                    }
                    
                    if (this.animationFrame) {
                        cancelAnimationFrame(this.animationFrame);
                        this.animationFrame = null;
                    }
                    if (this.audioContext) {
                        this.audioContext.close();
                        this.audioContext = null;
                        this.analyser = null;
                        this.microphone = null;
                    }
                    this.localVolume = 0;

                    Object.keys(this.peers).forEach(id => this.removePeer(id));
                    this.peers = {};
                    this.screenSharing = false;
                },

                async toggleMic() {
                    if (this.localStream) {
                        this.micEnabled = !this.micEnabled;
                        this.localStream.getAudioTracks().forEach(track => {
                            track.enabled = this.micEnabled;
                        });
                    } else {
                        try {
                            this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                            this.micEnabled = true;
                            // Add track to all existing peers and renegotiate
                            Object.values(this.peers).forEach(peer => {
                                this.localStream.getTracks().forEach(track => {
                                    peer.connection.addTrack(track, this.localStream);
                                });
                                this.renegotiate(peer);
                            });
                            this.setupAudioVisualizer();
                        } catch (e) {
                            console.error("Microphone access denied:", e);
                            alert("Microphone permission is required.");
                        }
                    }
                },

                async toggleScreenShare() {
                    if (!this.screenSharing) {
                        try {
                            this.localScreenStream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });
                            this.screenSharing = true;
                            
                            document.getElementById('localScreen').srcObject = this.localScreenStream;

                            // Add screen track to all peers
                            Object.values(this.peers).forEach(peer => {
                                this.localScreenStream.getTracks().forEach(track => {
                                    peer.connection.addTrack(track, this.localScreenStream);
                                });
                                // Renegotiate
                                this.renegotiate(peer);
                            });

                            // Stop screen sharing when native UI stop is clicked
                            this.localScreenStream.getVideoTracks()[0].onended = () => {
                                this.toggleScreenShare();
                            };

                        } catch (e) {
                            console.error("Screen share failed", e);
                        }
                    } else {
                        if (this.localScreenStream) {
                            this.localScreenStream.getTracks().forEach(track => track.stop());
                            // Remove tracks from peers
                            Object.values(this.peers).forEach(peer => {
                                peer.connection.getSenders().forEach(sender => {
                                    if (sender.track && sender.track.kind === 'video') {
                                        peer.connection.removeTrack(sender);
                                    }
                                });
                                this.renegotiate(peer);
                            });
                        }
                        this.localScreenStream = null;
                        this.screenSharing = false;
                        document.getElementById('localScreen').srcObject = null;
                    }
                },

                createPeerConnection(userId, isInitiator) {
                    const peerConnection = new RTCPeerConnection(this.iceServers);
                    
                    this.peers[userId] = {
                        connection: peerConnection,
                        stream: new MediaStream()
                    };

                    // Add local audio stream
                    if (this.localStream) {
                        this.localStream.getTracks().forEach(track => {
                            peerConnection.addTrack(track, this.localStream);
                        });
                    }

                    // Add local screen stream if sharing
                    if (this.localScreenStream) {
                        this.localScreenStream.getTracks().forEach(track => {
                            peerConnection.addTrack(track, this.localScreenStream);
                        });
                    }

                    peerConnection.ontrack = (event) => {
                        this.peers[userId].stream.addTrack(event.track);
                        this.renderRemoteVideo(userId, this.peers[userId].stream, event.track.kind);
                    };

                    peerConnection.onicecandidate = (event) => {
                        if (event.candidate) {
                            this.channel.whisper('webrtc-signal', {
                                target: userId,
                                sender: {{ auth()->id() ?? 'null' }},
                                signal: { type: 'candidate', candidate: event.candidate }
                            });
                        }
                    };

                    if (isInitiator) {
                        this.renegotiate(this.peers[userId], userId);
                    }
                },

                async renegotiate(peer, userId) {
                    const targetId = userId || Object.keys(this.peers).find(key => this.peers[key] === peer);
                    const offer = await peer.connection.createOffer();
                    await peer.connection.setLocalDescription(offer);
                    this.channel.whisper('webrtc-signal', {
                        target: targetId,
                        sender: {{ auth()->id() ?? 'null' }},
                        signal: { type: 'offer', sdp: offer }
                    });
                },

                async handleSignalingData(data) {
                    const userId = data.sender;
                    const myId = {{ auth()->id() ?? 'null' }};
                    
                    if (data.target != myId) return; // Not for me

                    if (!this.peers[userId]) {
                        this.createPeerConnection(userId, false);
                    }

                    const peerConnection = this.peers[userId].connection;

                    if (data.signal.type === 'offer') {
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.signal.sdp));
                        const answer = await peerConnection.createAnswer();
                        await peerConnection.setLocalDescription(answer);
                        this.channel.whisper('webrtc-signal', {
                            target: userId,
                            sender: myId,
                            signal: { type: 'answer', sdp: answer }
                        });
                    } else if (data.signal.type === 'answer') {
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.signal.sdp));
                    } else if (data.signal.type === 'candidate') {
                        await peerConnection.addIceCandidate(new RTCIceCandidate(data.signal.candidate));
                    }
                },

                removePeer(userId) {
                    if (this.peers[userId]) {
                        this.peers[userId].connection.close();
                        delete this.peers[userId];
                        const el = document.getElementById(`peer-${userId}`);
                        if (el) el.remove();
                    }
                },

                renderRemoteVideo(userId, stream, kind) {
                    let el = document.getElementById(`peer-${userId}`);
                    if (!el) {
                        const userName = this.usersInRoom[userId] ? this.usersInRoom[userId].name : `Participant ${userId}`;
                        const initials = userName.charAt(0).toUpperCase();

                        const grid = document.getElementById('participants-grid');
                        el = document.createElement('div');
                        el.id = `peer-${userId}`;
                        el.className = 'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center justify-center aspect-square relative overflow-hidden';
                        
                        el.innerHTML = `
                            <div class="w-16 h-16 rounded-full bg-brand-100 dark:bg-brand-900/50 text-brand-600 dark:text-brand-300 flex items-center justify-center text-xl font-bold mb-3 z-20 relative">
                                <span class="user-initials">${initials}</span>
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300 text-sm z-20 mb-3 user-name">${userName}</span>
                            <audio id="audio-${userId}" autoplay></audio>
                            <div id="video-container-${userId}" class="absolute inset-0 z-20 hidden group">
                                <video id="video-${userId}" onclick="this.requestFullscreen()" class="w-full h-full object-cover bg-black cursor-pointer transition-transform group-hover:scale-[1.02]" autoplay playsinline title="Klik untuk Maximize"></video>
                            </div>
                        `;
                        grid.appendChild(el);
                    }

                    if (kind === 'audio') {
                        document.getElementById(`audio-${userId}`).srcObject = stream;
                    } else if (kind === 'video') {
                        const videoEl = document.getElementById(`video-${userId}`);
                        videoEl.srcObject = stream;
                        document.getElementById(`video-container-${userId}`).classList.remove('hidden');
                        
                        // Handle removal of track cleanly
                        stream.onremovetrack = () => {
                            if (!stream.getVideoTracks().length) {
                                document.getElementById(`video-container-${userId}`).classList.add('hidden');
                                videoEl.srcObject = null;
                            }
                        };
                    }
                }
        }));
    </script>
    @endscript
</div>
