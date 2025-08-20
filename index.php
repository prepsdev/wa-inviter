<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Group Manager - Baileys Integration</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <!-- Socket.IO Client -->
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
        }
        
        /* Custom Success Color Override */
        .btn-success {
            background-color: #21414f !important;
            border-color: #21414f !important;
        }
        
        .btn-success:hover {
            background-color: #1a3540 !important;
            border-color: #1a3540 !important;
        }
        
        .bg-success {
            background-color: #21414f !important;
        }
        
        .text-success {
            color: #21414f !important;
        }
        
        .alert-success {
            background-color: #d1ecf1 !important;
            border-color: #bee5eb !important;
            color: #0c5460 !important;
        }
        
        /* Custom Primary Color Override */
        .btn-primary {
            background-color: #2c5aa0 !important;
            border-color: #2c5aa0 !important;
        }
        
        .btn-primary:hover {
            background-color: #1e4a8a !important;
            border-color: #1e4a8a !important;
        }
        
        .bg-primary {
            background-color: #2c5aa0 !important;
        }
        
        .text-primary {
            color: #2c5aa0 !important;
        }
        
        .alert-primary {
            background-color: #e3f2fd !important;
            border-color: #bbdefb !important;
            color: #1565c0 !important;
        }
        
        .qr-container {
            max-width: 300px;
            margin: 0 auto;
        }
        .status-badge {
            font-size: 0.9rem;
        }
        .group-card {
            transition: transform 0.2s;
        }
        .group-card:hover {
            transform: translateY(-2px);
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
        }
        .connection-status {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Connection Status Alert -->
    <div id="connectionStatus" class="connection-status">
        <div class="alert alert-info alert-dismissible" role="alert">
            <i class="bi bi-wifi"></i> <span id="statusText">Connecting to server...</span>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5 text-center">
                    <i class="bi bi-whatsapp text-success"></i> 
                    WhatsApp Group Manager
                </h1>
                <p class="lead text-center text-muted">Kelola grup WhatsApp menggunakan Baileys API</p>
            </div>
        </div>

        <!-- Status Card -->
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-signal"></i> Status Koneksi
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div id="statusDisplay">
                            <span id="statusBadge" class="badge bg-secondary status-badge">Disconnected</span>
                            <div class="mt-3">
                                <button id="connectBtn" class="btn btn-success me-2">
                                    <i class="bi bi-play-circle"></i> Connect
                                </button>
                                <button id="disconnectBtn" class="btn btn-danger" disabled>
                                    <i class="bi bi-stop-circle"></i> Disconnect
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Section -->
        <div class="row mb-4" id="qrSection" style="display: none;">
            <div class="col-md-6 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-qr-code"></i> Scan QR Code
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <p class="text-muted">Scan QR code berikut dengan aplikasi WhatsApp Anda:</p>
                        <div class="qr-container">
                            <div id="qrCodeDisplay">
                                <div class="spinner-border loading-spinner text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                Buka WhatsApp → Titik tiga → Linked Devices → Link a Device
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Groups Section -->
        <div class="row" id="groupsSection" style="display: none;">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-people"></i> Daftar Grup WhatsApp
                        </h5>
                        <button id="refreshGroupsBtn" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="groupsContainer">
                            <!-- Groups will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invite Modal -->
    <div class="modal fade" id="inviteModal" tabindex="-1" aria-labelledby="inviteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inviteModalLabel">
                        <i class="bi bi-people"></i> Invite Participants
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Group Information</h6>
                            <p><strong>Group:</strong> <span id="selectedGroupName"></span></p>
                            <p><strong>Group ID:</strong> <span id="selectedGroupId"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Message Template</h6>
                            <div class="mb-3">
                                <label for="namaDiklat" class="form-label">Nama Diklat</label>
                                <input type="text" class="form-control" id="namaDiklat" placeholder="Enter nama diklat">
                            </div>
                            <div class="mb-3">
                                <label for="kelompok" class="form-label">Kelompok</label>
                                <input type="text" class="form-control" id="kelompok" placeholder="Enter kelompok">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">Upload CSV File</label>
                        <input type="file" class="form-control" id="csvFile" accept=".csv" onchange="previewCSV(this)">
                        <div class="form-text">CSV format: name; phone</div>
                    </div>
                    
                    <div id="csvPreview" style="display: none;">
                        <h6>CSV Preview</h6>
                        <div class="table-responsive">
                                                         <table class="table table-sm table-striped" id="csvTable">
                                 <thead>
                                     <tr>
                                         <th>Name</th>
                                         <th>Phone</th>
                                         <th>Status</th>
                                         <th>Error</th>
                                     </tr>
                                 </thead>
                                 <tbody></tbody>
                             </table>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="messageTemplate" class="form-label">Message Template</label>
                        <textarea class="form-control" id="messageTemplate" rows="4" placeholder="Enter your message template with placeholders: {name}, {nama_diklat}, {kelompok}, {link}">Halo, {name}.

Kamu terdaftar sebagai peserta Diklat {nama_diklat} dengan group kelompok {kelompok}, silahkan klik link dibawah untuk bergabung kedalam group Whatsapp peserta Diklat {nama_diklat}.

{link}</textarea>
                    </div>
                    
                    <!-- Progress Section (hidden by default) -->
                    <div id="progressSection" style="display: none;">
                        <h6>Sending Progress</h6>
                        <div class="progress mb-2">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">
                            <span id="progressText">0 / 0 sent</span>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                     <button type="button" class="btn btn-warning me-2" id="sendValidOnlyBtn" onclick="sendInvites(true)">
                         <i class="bi bi-send"></i> Send Valid Only
                     </button>
                     <button type="button" class="btn btn-success" id="sendInvitesBtn" onclick="sendInvites(false)">
                         <i class="bi bi-send"></i> Send All
                     </button>
                </div>
            </div>
        </div>
         </div>
 
     <!-- Check Members Modal -->
     <div class="modal fade" id="checkModal" tabindex="-1" aria-labelledby="checkModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-lg">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="checkModalLabel">
                         <i class="bi bi-search"></i> Check Group Members
                     </h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <div class="row">
                         <div class="col-md-6">
                             <h6>Group Information</h6>
                             <p><strong>Group:</strong> <span id="checkGroupName"></span></p>
                             <p><strong>Group ID:</strong> <span id="checkGroupId"></span></p>
                         </div>
                         <div class="col-md-6">
                             <h6>Instructions</h6>
                             <p class="text-muted">Upload a CSV file with phone numbers to check which ones are already members of this group.</p>
                         </div>
                     </div>
                     
                     <hr>
                     
                     <div class="mb-3">
                         <label for="checkCsvFile" class="form-label">Upload CSV File</label>
                         <input type="file" class="form-control" id="checkCsvFile" accept=".csv" onchange="previewCheckCSV(this)">
                         <div class="form-text">CSV format: name; phone (or just phone numbers)</div>
                     </div>
                     
                     <div id="checkCsvPreview" style="display: none;">
                         <h6>CSV Preview</h6>
                         <div class="table-responsive">
                             <table class="table table-sm table-striped" id="checkCsvTable">
                                 <thead>
                                     <tr>
                                         <th>Name</th>
                                         <th>Phone</th>
                                         <th>Status</th>
                                         <th>Error</th>
                                     </tr>
                                 </thead>
                                 <tbody></tbody>
                             </table>
                         </div>
                     </div>
                     
                     <!-- Check Progress Section (hidden by default) -->
                     <div id="checkProgressSection" style="display: none;">
                         <h6>Checking Progress</h6>
                         <div class="progress mb-2">
                             <div id="checkProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                         </div>
                         <small class="text-muted">
                             <span id="checkProgressText">0 / 0 checked</span>
                         </small>
                     </div>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                     <button type="button" class="btn btn-primary" id="checkMembersBtn" onclick="checkMembers()">
                         <i class="bi bi-search"></i> Check Members
                     </button>
                 </div>
             </div>
         </div>
     </div>
 
     <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Configuration
        const API_BASE_URL = 'http://localhost:3000';
        
        // DOM Elements
        const statusBadge = document.getElementById('statusBadge');
        const statusText = document.getElementById('statusText');
        const connectBtn = document.getElementById('connectBtn');
        const disconnectBtn = document.getElementById('disconnectBtn');
        const qrSection = document.getElementById('qrSection');
        const qrCodeDisplay = document.getElementById('qrCodeDisplay');
        const groupsSection = document.getElementById('groupsSection');
        const groupsContainer = document.getElementById('groupsContainer');
        const refreshGroupsBtn = document.getElementById('refreshGroupsBtn');
        const connectionStatus = document.getElementById('connectionStatus');

        // Socket.IO connection
        let socket;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeSocketConnection();
            setupEventListeners();
            checkInitialStatus();
        });

        function initializeSocketConnection() {
            socket = io(API_BASE_URL);
            
            socket.on('connect', () => {
                updateConnectionStatus('Connected to server', 'success');
            });
            
            socket.on('disconnect', () => {
                updateConnectionStatus('Disconnected from server', 'danger');
            });
            
            socket.on('status', (data) => {
                updateStatus(data);
            });
            
            socket.on('qr_code', (data) => {
                showQRCode(data.qrCode);
                updateStatusBadge('qr_ready');
            });
            
            socket.on('status_update', (data) => {
                updateStatus(data);
            });
        }

        function setupEventListeners() {
            connectBtn.addEventListener('click', connectToWhatsApp);
            disconnectBtn.addEventListener('click', disconnectFromWhatsApp);
            refreshGroupsBtn.addEventListener('click', refreshGroups);
        }

        async function checkInitialStatus() {
            try {
                const response = await fetch(`${API_BASE_URL}/api/status`);
                const data = await response.json();
                updateStatus(data);
            } catch (error) {
                console.error('Error checking status:', error);
                updateConnectionStatus('Failed to connect to API server', 'danger');
            }
        }

        async function connectToWhatsApp() {
            try {
                connectBtn.disabled = true;
                connectBtn.innerHTML = '<i class="bi bi-spinner spinner-border spinner-border-sm"></i> Connecting...';
                
                const response = await fetch(`${API_BASE_URL}/api/connect`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    updateStatusBadge('connecting');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error connecting:', error);
                alert('Error: ' + error.message);
            } finally {
                connectBtn.disabled = false;
                connectBtn.innerHTML = '<i class="bi bi-play-circle"></i> Connect';
            }
        }

        async function disconnectFromWhatsApp() {
            try {
                disconnectBtn.disabled = true;
                disconnectBtn.innerHTML = '<i class="bi bi-spinner spinner-border spinner-border-sm"></i> Disconnecting...';
                
                const response = await fetch(`${API_BASE_URL}/api/disconnect`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error disconnecting:', error);
                alert('Error: ' + error.message);
            } finally {
                disconnectBtn.disabled = false;
                disconnectBtn.innerHTML = '<i class="bi bi-stop-circle"></i> Disconnect';
            }
        }

        async function refreshGroups() {
            try {
                refreshGroupsBtn.disabled = true;
                refreshGroupsBtn.innerHTML = '<i class="bi bi-spinner spinner-border spinner-border-sm"></i> Loading...';
                
                const response = await fetch(`${API_BASE_URL}/api/groups`);
                const data = await response.json();
                
                if (data.success) {
                    displayGroups(data.groups);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error refreshing groups:', error);
                alert('Error: ' + error.message);
            } finally {
                refreshGroupsBtn.disabled = false;
                refreshGroupsBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh';
            }
        }

        function updateStatus(data) {
            updateStatusBadge(data.status);
            
            if (data.qrCode) {
                showQRCode(data.qrCode);
            } else {
                hideQRCode();
            }
            
            if (data.groups && data.groups.length > 0) {
                displayGroups(data.groups);
                showGroupsSection();
            } else {
                hideGroupsSection();
            }
        }

        function updateStatusBadge(status) {
            const statusConfig = {
                'disconnected': { text: 'Disconnected', class: 'bg-secondary', buttons: { connect: false, disconnect: true } },
                'connecting': { text: 'Connecting...', class: 'bg-warning', buttons: { connect: true, disconnect: true } },
                'qr_ready': { text: 'QR Code Ready', class: 'bg-info', buttons: { connect: true, disconnect: false } },
                'connected': { text: 'Connected', class: 'bg-success', buttons: { connect: true, disconnect: false } },
                'logged_out': { text: 'Logged Out', class: 'bg-danger', buttons: { connect: false, disconnect: true } },
                'error': { text: 'Error', class: 'bg-danger', buttons: { connect: false, disconnect: true } },
                'reconnecting': { text: 'Reconnecting...', class: 'bg-warning', buttons: { connect: true, disconnect: true } }
            };
            
            const config = statusConfig[status] || statusConfig['disconnected'];
            
            statusBadge.textContent = config.text;
            statusBadge.className = `badge status-badge ${config.class}`;
            
            connectBtn.disabled = config.buttons.connect;
            disconnectBtn.disabled = config.buttons.disconnect;
        }

        function showQRCode(qrCodeData) {
            qrCodeDisplay.innerHTML = `<img src="${qrCodeData}" class="img-fluid rounded" alt="QR Code">`;
            qrSection.style.display = 'block';
        }

        function hideQRCode() {
            qrSection.style.display = 'none';
        }

        function showGroupsSection() {
            groupsSection.style.display = 'block';
        }

        function hideGroupsSection() {
            groupsSection.style.display = 'none';
        }

        function displayGroups(groups) {
            if (groups.length === 0) {
                groupsContainer.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="bi bi-inbox display-1"></i>
                        <p class="mt-3">Tidak ada grup ditemukan</p>
                    </div>
                `;
                return;
            }
            
            const groupsHtml = groups.map(group => `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card group-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-truncate" title="${group.name}">
                                <i class="bi bi-people-fill text-success"></i> ${group.name}
                            </h6>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> ${group.participants} anggota<br>
                                    <i class="bi bi-hash"></i> ${group.id}
                                </small>
                            </p>
                            ${group.description ? `<p class="card-text"><small>${group.description}</small></p>` : ''}
                            
                                                                                 <button class="btn btn-success btn-sm me-2" onclick="openInviteModal('${group.id}', '${group.name}')">
                            <i class="bi bi-upload"></i> Invite CSV
                        </button>
                        <button class="btn btn-primary btn-sm me-2" onclick="openCheckModal('${group.id}', '${group.name}')">
                            <i class="bi bi-search"></i> Check Members
                        </button>
                        <button class="btn btn-info btn-sm" onclick="listGroupMembers('${group.id}', '${group.name}')">
                            <i class="bi bi-list"></i> List Members
                        </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
            groupsContainer.innerHTML = `<div class="row">${groupsHtml}</div>`;
        }

        function copyGroupId(groupId) {
            navigator.clipboard.writeText(groupId).then(() => {
                // Show toast notification
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 mt-3 me-3';
                toast.style.zIndex = '1055';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-check-circle"></i> Group ID copied to clipboard!
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', () => {
                    document.body.removeChild(toast);
                });
            }).catch(err => {
                console.error('Failed to copy group ID:', err);
                alert('Failed to copy group ID');
            });
        }

        function updateConnectionStatus(message, type) {
            const alertClasses = {
                'success': 'alert-success',
                'danger': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            };
            
            const alertClass = alertClasses[type] || 'alert-info';
            statusText.textContent = message;
            connectionStatus.querySelector('.alert').className = `alert ${alertClass} alert-dismissible`;
        }

                 // Global variables for invite functionality
         let selectedGroupId = null;
         let selectedGroupName = null;
         let csvData = [];
         
         // Global variables for check members functionality
         let checkGroupId = null;
         let checkGroupName = null;
         let checkCsvData = [];

        function openInviteModal(groupId, groupName) {
            selectedGroupId = groupId;
            selectedGroupName = groupName;
            
            document.getElementById('selectedGroupId').textContent = groupId;
            document.getElementById('selectedGroupName').textContent = groupName;
            
            // Reset form
            document.getElementById('namaDiklat').value = '';
            document.getElementById('kelompok').value = '';
            document.getElementById('csvFile').value = '';
            document.getElementById('csvPreview').style.display = 'none';
            document.getElementById('csvTable').querySelector('tbody').innerHTML = '';
            document.getElementById('progressSection').style.display = 'none';
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressText').textContent = '0 / 0 sent';
            
            const modal = new bootstrap.Modal(document.getElementById('inviteModal'));
            modal.show();
        }

        function previewCSV(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split('\n');
                csvData = [];

                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (line) {
                        const parts = line.split(';');
                        if (parts.length >= 2) {
                            // Fix phone number format - convert scientific notation back to normal number
                            let phone = parts[1].trim();
                            
                            // Handle scientific notation (e.g., 6.28122E+12)
                            if (phone.includes('E') || phone.includes('e')) {
                                phone = parseFloat(phone).toString();
                            }
                            
                            // Remove any non-numeric characters except + and .
                            phone = phone.replace(/[^\d+.]/g, '');
                            
                            // If it's a decimal number, convert to integer
                            if (phone.includes('.')) {
                                phone = phone.split('.')[0];
                            }
                            
                            // Ensure it starts with country code
                            if (phone.startsWith('+')) {
                                phone = phone.substring(1);
                            }
                            
                            // If it doesn't start with 62, assume it's Indonesian
                            if (!phone.startsWith('62')) {
                                phone = '62' + phone.replace(/^0/, '');
                            }
                            
                                                         // Validate phone number length and format
                             if (phone.length < 12) {
                                 console.warn(`Invalid phone number for ${parts[0].trim()}: ${phone} (too short - minimum 12 digits required)`);
                                 csvData.push({
                                     name: parts[0].trim(),
                                     phone: phone,
                                     valid: false,
                                     error: `Phone number too short (${phone.length} digits, need 12+)`
                                 });
                             } else if (phone.length > 15) {
                                 console.warn(`Invalid phone number for ${parts[0].trim()}: ${phone} (too long)`);
                                 csvData.push({
                                     name: parts[0].trim(),
                                     phone: phone,
                                     valid: false,
                                     error: `Phone number too long (${phone.length} digits, max 15)`
                                 });
                             } else {
                                 csvData.push({
                                     name: parts[0].trim(),
                                     phone: phone,
                                     valid: true
                                 });
                             }
                        }
                    }
                }

                displayCSVPreview();
            };
            reader.readAsText(file);
        }

        function displayCSVPreview() {
            const tbody = document.getElementById('csvTable').querySelector('tbody');
            tbody.innerHTML = '';

            let validCount = 0;
            let invalidCount = 0;

            csvData.forEach((row, index) => {
                const tr = document.createElement('tr');
                const statusClass = row.valid ? 'table-success' : 'table-danger';
                const statusIcon = row.valid ? 'bi-check-circle text-success' : 'bi-x-circle text-danger';
                const statusText = row.valid ? 'Valid' : 'Invalid';
                
                tr.className = statusClass;
                tr.innerHTML = `
                    <td>${row.name}</td>
                    <td>${row.phone}</td>
                    <td><i class="bi ${statusIcon}"></i> ${statusText}</td>
                    <td>${row.error || '-'}</td>
                `;
                tbody.appendChild(tr);
                
                if (row.valid) validCount++;
                else invalidCount++;
            });

            // Add summary row
            const summaryRow = document.createElement('tr');
            summaryRow.className = 'table-info';
            summaryRow.innerHTML = `
                <td colspan="2"><strong>Summary:</strong></td>
                <td><strong>Valid: ${validCount} | Invalid: ${invalidCount}</strong></td>
                <td></td>
            `;
            tbody.appendChild(summaryRow);

            document.getElementById('csvPreview').style.display = 'block';
        }

        async function sendInvites(skipInvalid = false) {
            const namaDiklat = document.getElementById('namaDiklat').value.trim();
            const kelompok = document.getElementById('kelompok').value.trim();
            const messageTemplate = document.getElementById('messageTemplate').value.trim();

            if (!namaDiklat || !kelompok) {
                alert('Please fill in Nama Diklat and Kelompok fields');
                return;
            }

            if (csvData.length === 0) {
                alert('Please upload a CSV file first');
                return;
            }

            if (!messageTemplate) {
                alert('Please enter a message template');
                return;
            }
            
            // Check for invalid phone numbers
            const invalidPhones = csvData.filter(p => !p.valid);
            if (invalidPhones.length > 0) {
                if (skipInvalid) {
                    // Skip invalid numbers automatically
                    console.log(`Skipping ${invalidPhones.length} invalid phone numbers`);
                } else {
                    // Ask user for confirmation
                    const invalidList = invalidPhones.map(p => `${p.name}: ${p.phone}`).join('\n');
                    const confirmMessage = `Found ${invalidPhones.length} invalid phone number(s):\n\n${invalidList}\n\nThese will be skipped. Continue anyway?`;
                    if (!confirm(confirmMessage)) {
                        return;
                    }
                }
            }

            const sendBtn = document.getElementById('sendInvitesBtn');
            const originalText = sendBtn.innerHTML;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="bi bi-spinner spinner-border spinner-border-sm"></i> Sending...';

            // Show progress section
            document.getElementById('progressSection').style.display = 'block';
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const totalParticipants = csvData.length;

            // Array to store detailed logs
            const logData = [];

            try {
                // First, get the invite link for the group
                const inviteResponse = await fetch(`${API_BASE_URL}/api/invite-link`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        groupId: selectedGroupId
                    })
                });

                const inviteData = await inviteResponse.json();
                
                if (!inviteData.success) {
                    throw new Error('Failed to get invite link: ' + inviteData.message);
                }

                const inviteLink = inviteData.inviteLink;

                // Send messages to each participant
                let successCount = 0;
                let errorCount = 0;

                for (let i = 0; i < csvData.length; i++) {
                    const participant = csvData[i];
                    
                    // Update progress
                    const progress = ((i + 1) / totalParticipants) * 100;
                    progressBar.style.width = progress + '%';
                    progressText.textContent = `${i + 1} / ${totalParticipants} processed`;
                    
                    // Skip invalid phone numbers if requested
                    if (!participant.valid) {
                        if (skipInvalid) {
                            errorCount++;
                            logData.push({
                                name: participant.name,
                                phone: participant.phone,
                                status: 'Skipped',
                                reason: participant.error || 'Invalid phone number',
                                timestamp: new Date().toLocaleString('id-ID')
                            });
                            continue; // Skip to next participant
                        } else {
                            // Try to send anyway (user chose "Send All")
                            console.log(`Attempting to send to invalid number: ${participant.phone}`);
                        }
                    }
                    try {
                        const message = messageTemplate
                            .replace(/{name}/g, participant.name)
                            .replace(/{nama_diklat}/g, namaDiklat)
                            .replace(/{kelompok}/g, kelompok)
                            .replace(/{link}/g, inviteLink);

                        const response = await fetch(`${API_BASE_URL}/api/send-message`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                phone: participant.phone,
                                message: message
                            })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            successCount++;
                            logData.push({
                                name: participant.name,
                                phone: participant.phone,
                                status: 'Success',
                                reason: 'Message sent successfully',
                                timestamp: new Date().toLocaleString('id-ID')
                            });
                        } else {
                            errorCount++;
                            logData.push({
                                name: participant.name,
                                phone: participant.phone,
                                status: 'Failed',
                                reason: data.message || 'Unknown error',
                                timestamp: new Date().toLocaleString('id-ID')
                            });
                            console.error(`Failed to send to ${participant.phone}:`, data.message);
                        }

                        // Add a small delay to avoid rate limiting
                        await new Promise(resolve => setTimeout(resolve, 1000));

                    } catch (error) {
                        errorCount++;
                        logData.push({
                            name: participant.name,
                            phone: participant.phone,
                            status: 'Failed',
                            reason: error.message || 'Network error',
                            timestamp: new Date().toLocaleString('id-ID')
                        });
                        console.error(`Error sending to ${participant.phone}:`, error);
                    }
                }

                // Show results and offer to download log
                const modal = bootstrap.Modal.getInstance(document.getElementById('inviteModal'));
                modal.hide();

                const resultMessage = `Invites sent!\nSuccess: ${successCount}\nFailed: ${errorCount}\n\nWould you like to download the detailed log?`;
                
                if (confirm(resultMessage)) {
                    await generateExcelLog(logData, selectedGroupName, namaDiklat, kelompok);
                }

            } catch (error) {
                console.error('Error sending invites:', error);
                alert('Error: ' + error.message);
            } finally {
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalText;
            }
        }

        async function generateExcelLog(logData, groupName, namaDiklat, kelompok) {
            try {
                const response = await fetch(`${API_BASE_URL}/api/generate-log`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        logData: logData,
                        groupName: groupName,
                        namaDiklat: namaDiklat,
                        kelompok: kelompok
                    })
                });

                if (response.ok) {
                    // Create a blob from the response
                    const blob = await response.blob();
                    
                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    
                    // Generate filename
                    const timestamp = new Date().toISOString().split('T')[0];
                    const filename = `invite_log_${groupName?.replace(/[^a-zA-Z0-9]/g, '_') || 'group'}_${timestamp}.xlsx`;
                    a.download = filename;
                    
                    // Trigger download
                    document.body.appendChild(a);
                    a.click();
                    
                    // Cleanup
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    alert('Excel log file downloaded successfully!');
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to generate log');
                }
            } catch (error) {
                console.error('Error generating Excel log:', error);
                                 alert('Error generating log: ' + error.message);
             }
         }
         
         // Check Members Functions
         function openCheckModal(groupId, groupName) {
             checkGroupId = groupId;
             checkGroupName = groupName;
             
             document.getElementById('checkGroupId').textContent = groupId;
             document.getElementById('checkGroupName').textContent = groupName;
             
             // Reset form
             document.getElementById('checkCsvFile').value = '';
             document.getElementById('checkCsvPreview').style.display = 'none';
             document.getElementById('checkCsvTable').querySelector('tbody').innerHTML = '';
             document.getElementById('checkProgressSection').style.display = 'none';
             document.getElementById('checkProgressBar').style.width = '0%';
             document.getElementById('checkProgressText').textContent = '0 / 0 checked';
             
             const modal = new bootstrap.Modal(document.getElementById('checkModal'));
             modal.show();
         }
         
         function previewCheckCSV(input) {
             const file = input.files[0];
             if (!file) return;
 
             const reader = new FileReader();
             reader.onload = function(e) {
                 const text = e.target.result;
                 const lines = text.split('\n');
                 checkCsvData = [];
 
                 for (let i = 0; i < lines.length; i++) {
                     const line = lines[i].trim();
                     if (line) {
                         const parts = line.split(';');
                         let name = '';
                         let phone = '';
                         
                         if (parts.length >= 2) {
                             // Format: name; phone
                             name = parts[0].trim();
                             phone = parts[1].trim();
                         } else if (parts.length === 1) {
                             // Format: just phone number
                             phone = parts[0].trim();
                             name = phone; // Use phone as name for display
                         }
                         
                         if (phone) {
                             // Fix phone number format - convert scientific notation back to normal number
                             if (phone.includes('E') || phone.includes('e')) {
                                 phone = parseFloat(phone).toString();
                             }
                             
                             // Remove any non-numeric characters except + and .
                             phone = phone.replace(/[^\d+.]/g, '');
                             
                             // If it's a decimal number, convert to integer
                             if (phone.includes('.')) {
                                 phone = phone.split('.')[0];
                             }
                             
                             // Ensure it starts with country code
                             if (phone.startsWith('+')) {
                                 phone = phone.substring(1);
                             }
                             
                             // If it doesn't start with 62, assume it's Indonesian
                             if (!phone.startsWith('62')) {
                                 phone = '62' + phone.replace(/^0/, '');
                             }
                             
                                                           // Validate phone number length and format
                              if (phone.length < 12) {
                                  console.warn(`Invalid phone number for ${name}: ${phone} (too short - minimum 12 digits required)`);
                                  checkCsvData.push({
                                      name: name,
                                      phone: phone,
                                      valid: false,
                                      error: `Phone number too short (${phone.length} digits, need 12+)`
                                  });
                              } else if (phone.length > 15) {
                                  console.warn(`Invalid phone number for ${name}: ${phone} (too long)`);
                                  checkCsvData.push({
                                      name: name,
                                      phone: phone,
                                      valid: false,
                                      error: `Phone number too long (${phone.length} digits, max 15)`
                                  });
                              } else {
                                  checkCsvData.push({
                                      name: name,
                                      phone: phone,
                                      valid: true
                                  });
                              }
                         }
                     }
                 }
 
                 displayCheckCSVPreview();
             };
             reader.readAsText(file);
         }
         
         function displayCheckCSVPreview() {
             const tbody = document.getElementById('checkCsvTable').querySelector('tbody');
             tbody.innerHTML = '';
 
             let validCount = 0;
             let invalidCount = 0;
 
             checkCsvData.forEach((row, index) => {
                 const tr = document.createElement('tr');
                 const statusClass = row.valid ? 'table-success' : 'table-danger';
                 const statusIcon = row.valid ? 'bi-check-circle text-success' : 'bi-x-circle text-danger';
                 const statusText = row.valid ? 'Valid' : 'Invalid';
                 
                 tr.className = statusClass;
                 tr.innerHTML = `
                     <td>${row.name}</td>
                     <td>${row.phone}</td>
                     <td><i class="bi ${statusIcon}"></i> ${statusText}</td>
                     <td>${row.error || '-'}</td>
                 `;
                 tbody.appendChild(tr);
                 
                 if (row.valid) validCount++;
                 else invalidCount++;
             });
 
             // Add summary row
             const summaryRow = document.createElement('tr');
             summaryRow.className = 'table-info';
             summaryRow.innerHTML = `
                 <td colspan="2"><strong>Summary:</strong></td>
                 <td><strong>Valid: ${validCount} | Invalid: ${invalidCount}</strong></td>
                 <td></td>
             `;
             tbody.appendChild(summaryRow);
 
             document.getElementById('checkCsvPreview').style.display = 'block';
         }
         
         // Function to list group members
         async function listGroupMembers(groupId, groupName) {
             try {
                                   const response = await fetch('http://localhost:3000/api/group-participants', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json'
                     },
                     body: JSON.stringify({ groupId })
                 });

                 const data = await response.json();
                 
                 if (data.success) {
                     // Create a detailed display of all participant data
                     let memberList = '<div class="alert alert-info"><h5>Group Members Data for: ' + groupName + '</h5>';
                     memberList += '<p><strong>Total Participants:</strong> ' + data.participants.length + '</p>';
                     memberList += '<hr>';
                     
                     data.participants.forEach((participant, index) => {
                         memberList += '<div class="mb-3 p-3 border rounded">';
                         memberList += '<h6>Participant #' + (index + 1) + '</h6>';
                         memberList += '<p><strong>ID:</strong> ' + participant.id + '</p>';
                         memberList += '<p><strong>Admin:</strong> ' + (participant.admin || 'No') + '</p>';
                         
                         // Show extracted phone if available
                         if (participant.extractedPhone) {
                             memberList += '<p><strong>Extracted Phone:</strong> ' + participant.extractedPhone + '</p>';
                         }
                         
                         // Show contact info if available
                         if (participant.contactInfo) {
                             memberList += '<p><strong>Contact Info:</strong></p>';
                             memberList += '<pre class="bg-light p-2 rounded"><code>' + JSON.stringify(participant.contactInfo, null, 2) + '</code></pre>';
                         }
                         
                         // Show user info if available
                         if (participant.userInfo) {
                             memberList += '<p><strong>User Info:</strong></p>';
                             memberList += '<pre class="bg-light p-2 rounded"><code>' + JSON.stringify(participant.userInfo, null, 2) + '</code></pre>';
                         }
                         
                         // Show the raw participant object for debugging
                         memberList += '<p><strong>Raw Data:</strong></p>';
                         memberList += '<pre class="bg-light p-2 rounded"><code>' + JSON.stringify(participant.rawData, null, 2) + '</code></pre>';
                         memberList += '</div>';
                     });
                     
                     memberList += '</div>';
                     
                     // Show in a modal
                     const modal = document.createElement('div');
                     modal.className = 'modal fade';
                     modal.id = 'memberListModal';
                     modal.innerHTML = `
                         <div class="modal-dialog modal-xl">
                             <div class="modal-content">
                                 <div class="modal-header">
                                     <h5 class="modal-title">Group Members Data - ${groupName}</h5>
                                     <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                 </div>
                                 <div class="modal-body">
                                     ${memberList}
                                 </div>
                                 <div class="modal-footer">
                                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                 </div>
                             </div>
                         </div>
                     `;
                     
                     document.body.appendChild(modal);
                     const modalInstance = new bootstrap.Modal(modal);
                     modalInstance.show();
                     
                     // Clean up modal after it's hidden
                     modal.addEventListener('hidden.bs.modal', () => {
                         document.body.removeChild(modal);
                     });
                     
                 } else {
                     alert('Error: ' + data.message);
                 }
             } catch (error) {
                 console.error('Error listing group members:', error);
                 alert('Error listing group members: ' + error.message);
             }
         }
         
         async function checkMembers() {
              if (checkCsvData.length === 0) {
                  alert('Please upload a CSV file first');
                  return;
              }
  
              const checkBtn = document.getElementById('checkMembersBtn');
              const originalText = checkBtn.innerHTML;
              checkBtn.disabled = true;
              checkBtn.innerHTML = '<i class="bi bi-spinner spinner-border spinner-border-sm"></i> Checking...';
  
              // Show progress section
              document.getElementById('checkProgressSection').style.display = 'block';
              const progressBar = document.getElementById('checkProgressBar');
              const progressText = document.getElementById('checkProgressText');
              const totalParticipants = checkCsvData.length;
  
              try {
                  // Update progress
                  progressBar.style.width = '50%';
                  progressText.textContent = 'Getting group participants...';
  
                  // Use the new improved check-members-v2 API endpoint
                  const checkResponse = await fetch(`${API_BASE_URL}/api/check-members-v2`, {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json'
                      },
                      body: JSON.stringify({
                          groupId: checkGroupId,
                          phoneNumbers: checkCsvData.map(p => ({
                              name: p.name,
                              phone: p.phone
                          }))
                      })
                  });
  
                  const checkData = await checkResponse.json();
                  
                  if (!checkData.success) {
                      throw new Error('Failed to check members: ' + checkData.message);
                  }
  
                  // Update progress
                  progressBar.style.width = '100%';
                  progressText.textContent = 'Processing results...';
  
                  // Convert results to log format
                  const logData = checkData.results.map(result => ({
                      name: result.name,
                      phone: result.phone,
                      status: result.status,
                      reason: result.reason,
                      timestamp: new Date().toLocaleString('id-ID')
                  }));
  
                  // Show results and offer to download log
                  const modal = bootstrap.Modal.getInstance(document.getElementById('checkModal'));
                  modal.hide();
  
                  const resultMessage = `Member check completed!\nMembers: ${checkData.members}\nNot Members: ${checkData.notMembers}\nErrors: ${checkData.errors}\n\nWould you like to download the detailed log?`;
                  
                  if (confirm(resultMessage)) {
                      await generateCheckExcelLog(logData, checkGroupName);
                  }
  
              } catch (error) {
                  console.error('Error checking members:', error);
                  alert('Error: ' + error.message);
              } finally {
                  checkBtn.disabled = false;
                  checkBtn.innerHTML = originalText;
              }
          }
         
         async function generateCheckExcelLog(logData, groupName) {
             try {
                 const response = await fetch(`${API_BASE_URL}/api/generate-check-log`, {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json'
                     },
                     body: JSON.stringify({
                         logData: logData,
                         groupName: groupName
                     })
                 });
 
                 if (response.ok) {
                     // Create a blob from the response
                     const blob = await response.blob();
                     
                     // Create download link
                     const url = window.URL.createObjectURL(blob);
                     const a = document.createElement('a');
                     a.style.display = 'none';
                     a.href = url;
                     
                     // Generate filename
                     const timestamp = new Date().toISOString().split('T')[0];
                     const filename = `member_check_${groupName?.replace(/[^a-zA-Z0-9]/g, '_') || 'group'}_${timestamp}.xlsx`;
                     a.download = filename;
                     
                     // Trigger download
                     document.body.appendChild(a);
                     a.click();
                     
                     // Cleanup
                     window.URL.revokeObjectURL(url);
                     document.body.removeChild(a);
                     
                     alert('Member check log file downloaded successfully!');
                 } else {
                     const errorData = await response.json();
                     throw new Error(errorData.message || 'Failed to generate check log');
                 }
             } catch (error) {
                 console.error('Error generating check Excel log:', error);
                 alert('Error generating check log: ' + error.message);
             }
         }
     </script>
</body>
</html>
