const express = require('express');
const { default: makeWASocket, DisconnectReason, useMultiFileAuthState } = require('@whiskeysockets/baileys');
const QRCode = require('qrcode');
const cors = require('cors');
const { createServer } = require('http');
const { Server } = require('socket.io');
const ExcelJS = require('exceljs');
const path = require('path');
const fs = require('fs');

const app = express();
const server = createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

let sock = null;
let qrCodeData = null;
let connectionStatus = 'disconnected';
let groups = [];

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);
    
    socket.emit('status', {
        status: connectionStatus,
        qrCode: qrCodeData,
        groups: groups
    });
    
    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
    });
});

async function connectToWhatsApp() {
    try {
        const { state, saveCreds } = await useMultiFileAuthState('auth_info_baileys');
        
        sock = makeWASocket({
            auth: state,
            printQRInTerminal: false,
            connectTimeoutMs: 60000,
            defaultQueryTimeoutMs: 30000,
            linkPreview: false
        });

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;
            
            if (qr) {
                try {
                    qrCodeData = await QRCode.toDataURL(qr);
                    connectionStatus = 'qr_ready';
                    
                    io.emit('qr_code', {
                        qrCode: qrCodeData,
                        status: connectionStatus
                    });
                    
                    console.log('QR Code generated and sent to clients');
                } catch (err) {
                    console.error('Error generating QR code:', err);
                }
            }
            
            if (connection === 'close') {
                const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
                console.log('Connection closed due to ', lastDisconnect?.error, ', reconnecting ', shouldReconnect);
                
                if (shouldReconnect) {
                    connectionStatus = 'reconnecting';
                    io.emit('status_update', { status: connectionStatus });
                    connectToWhatsApp();
                } else {
                    connectionStatus = 'logged_out';
                    qrCodeData = null;
                    groups = [];
                    io.emit('status_update', { 
                        status: connectionStatus,
                        qrCode: null,
                        groups: []
                    });
                }
            } else if (connection === 'open') {
                connectionStatus = 'connected';
                qrCodeData = null;
                
                console.log('WhatsApp connected successfully!');
                
                await getGroups();
                
                io.emit('status_update', {
                    status: connectionStatus,
                    qrCode: null,
                    groups: groups
                });
            }
        });

        sock.ev.on('creds.update', saveCreds);
        
    } catch (error) {
        console.error('Error connecting to WhatsApp:', error);
        connectionStatus = 'error';
        io.emit('status_update', { status: connectionStatus, error: error.message });
    }
}

async function getGroups() {
    try {
        if (!sock) {
            throw new Error('WhatsApp not connected');
        }
        
        const groupsData = await sock.groupFetchAllParticipating();
        groups = Object.keys(groupsData).map(key => ({
            id: key,
            name: groupsData[key].subject || 'Unknown Group',
            participants: groupsData[key].participants?.length || 0,
            description: groupsData[key].desc || ''
        }));
        
        console.log(`Found ${groups.length} groups`);
        return groups;
    } catch (error) {
        console.error('Error fetching groups:', error);
        throw error;
    }
}

app.get('/api/status', (req, res) => {
    res.json({
        status: connectionStatus,
        qrCode: qrCodeData,
        groups: groups
    });
});

app.get('/api/qr', (req, res) => {
    if (qrCodeData) {
        res.json({
            success: true,
            qrCode: qrCodeData,
            status: connectionStatus
        });
    } else {
        res.json({
            success: false,
            message: 'QR Code not available',
            status: connectionStatus
        });
    }
});

app.get('/api/groups', async (req, res) => {
    try {
        if (connectionStatus !== 'connected') {
            return res.status(400).json({
                success: false,
                message: 'WhatsApp not connected'
            });
        }
        
        const groupsData = await getGroups();
        res.json({
            success: true,
            groups: groupsData
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

app.post('/api/connect', (req, res) => {
    if (connectionStatus === 'disconnected' || connectionStatus === 'logged_out') {
        connectToWhatsApp();
        res.json({
            success: true,
            message: 'Connection initiated'
        });
    } else {
        res.json({
            success: false,
            message: 'Already connected or connecting',
            status: connectionStatus
        });
    }
});

app.post('/api/disconnect', async (req, res) => {
    try {
        if (sock) {
            await sock.logout();
            sock = null;
        }
        connectionStatus = 'logged_out';
        qrCodeData = null;
        groups = [];
        
        io.emit('status_update', {
            status: connectionStatus,
            qrCode: null,
            groups: []
        });
        
        res.json({
            success: true,
            message: 'Disconnected successfully'
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

app.post('/api/invite-link', async (req, res) => {
    try {
        const { groupId } = req.body;
        
        if (!sock) {
            return res.status(400).json({
                success: false,
                message: 'WhatsApp not connected'
            });
        }
        
        if (!groupId) {
            return res.status(400).json({
                success: false,
                message: 'Group ID is required'
            });
        }
        
        const inviteCode = await sock.groupInviteCode(groupId);
        const inviteLink = `https://chat.whatsapp.com/${inviteCode}`;
        
        res.json({
            success: true,
            inviteLink: inviteLink,
            inviteCode: inviteCode
        });
        
    } catch (error) {
        console.error('Error generating invite link:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

app.post('/api/send-message', async (req, res) => {
    try {
        const { phone, message } = req.body;
        
        if (!sock) {
            return res.status(400).json({
                success: false,
                message: 'WhatsApp not connected'
            });
        }
        
        if (!phone || !message) {
            return res.status(400).json({
                success: false,
                message: 'Phone number and message are required'
            });
        }
        
        let formattedPhone = phone.replace(/^\+/, '').replace(/\s/g, '');
        
        if (formattedPhone.length < 10) {
            return res.status(400).json({
                success: false,
                message: 'Invalid phone number: too short',
                phone: phone
            });
        }
        
        if (!formattedPhone.startsWith('62')) {
            formattedPhone = '62' + formattedPhone.replace(/^0/, '');
        }
        
        if (formattedPhone.length < 12) {
            return res.status(400).json({
                success: false,
                message: 'Invalid phone number: incomplete number',
                phone: phone
            });
        }
        
        const jid = `${formattedPhone}@s.whatsapp.net`;
        
        let retries = 3;
        let lastError = null;
        
        for (let attempt = 1; attempt <= retries; attempt++) {
            try {
                await sock.sendMessage(jid, { 
                    text: message 
                }, {
                    linkPreview: false
                });
                
                break;
                
            } catch (error) {
                lastError = error;
                console.log(`Attempt ${attempt} failed for ${formattedPhone}: ${error.message}`);
                
                if (attempt < retries) {
                    await new Promise(resolve => setTimeout(resolve, 2000 * attempt));
                }
            }
        }
        
        if (lastError) {
            throw lastError;
        }
        
        console.log(`Message sent to ${formattedPhone}: ${message.substring(0, 50)}...`);
        
        res.json({
            success: true,
            message: 'Message sent successfully',
            phone: formattedPhone
        });
        
    } catch (error) {
        console.error('Error sending message:', error);
        
        let errorMessage = error.message;
        if (error.message.includes('timeout') || error.message.includes('RTO')) {
            errorMessage = 'Request timeout - please try again';
        } else if (error.message.includes('not-authorized')) {
            errorMessage = 'Phone number not authorized or invalid';
        } else if (error.message.includes('forbidden')) {
            errorMessage = 'Message blocked - phone number may be restricted';
        }
        
        res.status(500).json({
            success: false,
            message: errorMessage
        });
    }
});

app.post('/api/generate-log', async (req, res) => {
    try {
        const { logData, groupName, namaDiklat, kelompok } = req.body;
        
        if (!logData || !Array.isArray(logData)) {
            return res.status(400).json({
                success: false,
                message: 'Log data is required'
            });
        }
        
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet('Invite Log');
        
        worksheet.columns = [
            { header: 'Name', key: 'name', width: 30 },
            { header: 'Phone', key: 'phone', width: 20 },
            { header: 'Status', key: 'status', width: 15 },
            { header: 'Reason', key: 'reason', width: 40 },
            { header: 'Timestamp', key: 'timestamp', width: 20 }
        ];
        
        const headerRow = worksheet.getRow(1);
        if (headerRow.font) {
            headerRow.font.bold = true;
        }
        headerRow.fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF4F81BD' }
        };
        if (headerRow.font) {
            headerRow.font.color = { argb: 'FFFFFFFF' };
        }
        
        logData.forEach((item, index) => {
            const row = worksheet.addRow({
                name: item.name,
                phone: item.phone,
                status: item.status,
                reason: item.reason || '',
                timestamp: item.timestamp || new Date().toLocaleString('id-ID')
            });
            
            const statusCell = row.getCell('status');
            if (item.status === 'Success') {
                statusCell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFC6EFCE' }
                };
                if (statusCell.font) {
                    statusCell.font.color = { argb: 'FF006100' };
                }
            } else if (item.status === 'Failed') {
                statusCell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFFFC7CE' }
                };
                if (statusCell.font) {
                    statusCell.font.color = { argb: 'FF9C0006' };
                }
            }
        });
        
        worksheet.addRow([]);
        const summaryRow1 = worksheet.addRow(['Summary Information']);
        summaryRow1.font = { bold: true };
        summaryRow1.getCell(1).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFF2F2F2' }
        };
        
        worksheet.addRow(['Group Name', groupName || 'N/A']);
        worksheet.addRow(['Nama Diklat', namaDiklat || 'N/A']);
        worksheet.addRow(['Kelompok', kelompok || 'N/A']);
        worksheet.addRow(['Total Participants', logData.length]);
        worksheet.addRow(['Success', logData.filter(item => item.status === 'Success').length]);
        worksheet.addRow(['Failed', logData.filter(item => item.status === 'Failed').length]);
        worksheet.addRow(['Generated At', new Date().toLocaleString('id-ID')]);
        
        const logsDir = path.join(__dirname, 'logs');
        if (!fs.existsSync(logsDir)) {
            fs.mkdirSync(logsDir);
        }
        
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').split('T')[0];
        const filename = `invite_log_${groupName?.replace(/[^a-zA-Z0-9]/g, '_') || 'group'}_${timestamp}.xlsx`;
        const filepath = path.join(logsDir, filename);
        
        await workbook.xlsx.writeFile(filepath);
        
        res.download(filepath, filename, (err) => {
            if (err) {
                console.error('Error sending file:', err);
                res.status(500).json({
                    success: false,
                    message: 'Error sending file'
                });
            } else {
                setTimeout(() => {
                    fs.unlink(filepath, (unlinkErr) => {
                        if (unlinkErr) console.error('Error deleting file:', unlinkErr);
                    });
                }, 5000);
            }
        });
        
    } catch (error) {
        console.error('Error generating Excel log:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

app.post('/api/group-participants', async (req, res) => {
    try {
        const { groupId } = req.body;

        if (!groupId) {
            return res.status(400).json({
                success: false,
                message: 'Group ID is required'
            });
        }

        if (!sock) {
            return res.status(400).json({
                success: false,
                message: 'WhatsApp not connected'
            });
        }

        const groupData = await sock.groupMetadata(groupId);
        const participants = groupData.participants || [];
        
        console.log('Group participants:', participants);

        const detailedParticipants = [];
        
        for (const participant of participants) {
            const detailedParticipant = {
                id: participant.id,
                admin: participant.admin,
                rawData: participant
            };
            
            try {
                if (sock.contacts && sock.contacts[participant.id]) {
                    const contact = sock.contacts[participant.id];
                    detailedParticipant.contactInfo = contact;
                    console.log(`Found contact info for ${participant.id}:`, contact);
                }
                
                try {
                    const userInfo = await sock.user(participant.id);
                    detailedParticipant.userInfo = userInfo;
                    console.log(`Found user info for ${participant.id}:`, userInfo);
                } catch (userError) {
                    console.log(`Could not get user info for ${participant.id}:`, userError.message);
                }
                
                if (participant.id.includes('@s.whatsapp.net')) {
                    const phone = participant.id.replace('@s.whatsapp.net', '');
                    detailedParticipant.extractedPhone = phone;
                    console.log(`Extracted phone from ID: ${phone}`);
                }
                
            } catch (error) {
                console.log(`Error getting additional info for ${participant.id}:`, error.message);
            }
            
            detailedParticipants.push(detailedParticipant);
        }

        res.json({
            success: true,
            participants: detailedParticipants,
            totalParticipants: detailedParticipants.length
        });

    } catch (error) {
        console.error('Error getting group participants:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to get group participants: ' + error.message
        });
    }
});

app.post('/api/check-members', async (req, res) => {
    try {
        const { groupId, phoneNumbers } = req.body;

        if (!groupId) {
            return res.status(400).json({
                success: false,
                message: 'Group ID is required'
            });
        }

        if (!phoneNumbers || !Array.isArray(phoneNumbers)) {
            return res.status(400).json({
                success: false,
                message: 'Phone numbers array is required'
            });
        }

        if (!sock) {
            return res.status(400).json({
                success: false,
                message: 'WhatsApp not connected'
            });
        }

        const groupData = await sock.groupMetadata(groupId);
        const participants = groupData.participants || [];
        
        console.log('Group participants:', participants);

        const participantPhoneNumbers = new Set();
        
        for (const participant of participants) {
            try {
                if (participant.id.includes('@s.whatsapp.net')) {
                    const phone = participant.id.replace('@s.whatsapp.net', '');
                    participantPhoneNumbers.add(phone);
                    console.log(`Found phone number from participant: ${phone}`);
                } else if (participant.id.includes('@lid')) {
                    try {
                        if (sock.contacts && sock.contacts[participant.id]) {
                            const contact = sock.contacts[participant.id];
                            if (contact.id && contact.id.includes('@s.whatsapp.net')) {
                                const phone = contact.id.replace('@s.whatsapp.net', '');
                                participantPhoneNumbers.add(phone);
                                console.log(`Found phone number from contacts: ${phone}`);
                            }
                        }
                        
                        try {
                            const userInfo = await sock.user(participant.id);
                            if (userInfo && userInfo.id && userInfo.id.includes('@s.whatsapp.net')) {
                                const phone = userInfo.id.replace('@s.whatsapp.net', '');
                                participantPhoneNumbers.add(phone);
                                console.log(`Found phone number from user info: ${phone}`);
                            }
                        } catch (userError) {
                            console.log(`Could not get user info for ${participant.id}:`, userError.message);
                        }
                        
                        if (typeof sock.contactsUpsert === 'function') {
                            try {
                                const contact = await sock.contactsUpsert([{
                                    id: participant.id,
                                    name: 'Unknown'
                                }]);
                                if (contact && contact.id && contact.id.includes('@s.whatsapp.net')) {
                                    const phone = contact.id.replace('@s.whatsapp.net', '');
                                    participantPhoneNumbers.add(phone);
                                    console.log(`Found phone number from contactsUpsert: ${phone}`);
                                }
                            } catch (contactError) {
                                console.log(`Could not get contact info via contactsUpsert for ${participant.id}:`, contactError.message);
                            }
                        }
                        
                    } catch (error) {
                        console.log(`Could not get contact info for ${participant.id}:`, error.message);
                    }
                }
            } catch (error) {
                console.log(`Error processing participant ${participant.id}:`, error.message);
            }
        }
        
        console.log('Participant phone numbers found:', Array.from(participantPhoneNumbers));

        const results = [];
        
        for (const phoneData of phoneNumbers) {
            const { name, phone } = phoneData;
            
            try {
                let formattedPhone = phone.replace(/^\+/, '').replace(/\s/g, '');
                
                if (!formattedPhone.startsWith('62')) {
                    formattedPhone = '62' + formattedPhone.replace(/^0/, '');
                }
                
                const isMember = participantPhoneNumbers.has(formattedPhone);
                
                results.push({
                    name: name,
                    phone: phone,
                    formattedPhone: formattedPhone,
                    isMember: isMember,
                    status: isMember ? 'Member' : 'Not Member',
                    reason: isMember ? 'Found in group participants' : 'Not found in group participants'
                });
                
            } catch (error) {
                results.push({
                    name: name,
                    phone: phone,
                    formattedPhone: null,
                    isMember: false,
                    status: 'Error',
                    reason: 'Error processing phone number: ' + error.message
                });
            }
        }

        res.json({
            success: true,
            results: results,
            totalChecked: results.length,
            members: results.filter(r => r.isMember).length,
            notMembers: results.filter(r => !r.isMember && r.status !== 'Error').length,
            errors: results.filter(r => r.status === 'Error').length,
            participantPhoneNumbers: Array.from(participantPhoneNumbers)
        });

    } catch (error) {
        console.error('Error checking members:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to check members: ' + error.message
        });
    }
});

app.post('/api/check-members-v2', async (req, res) => {
    try {
        const { groupId, phoneNumbers } = req.body;

        if (!groupId) {
            return res.status(400).json({
                success: false,
                message: 'Group ID is required'
            });
        }

        if (!phoneNumbers || !Array.isArray(phoneNumbers)) {
            return res.status(400).json({
                success: false,
                message: 'Phone numbers array is required'
            });
        }

        if (!sock) {
            return res.status(400).json({
                success: false,
                message: 'WhatsApp not connected'
            });
        }

        const groupData = await sock.groupMetadata(groupId);
        const participants = groupData.participants || [];
        
        console.log('Group participants:', participants);
        console.log('Total participants in group:', participants.length);
        
        const results = [];
        let memberCount = 0;
        let notMemberCount = 0;
        let errorCount = 0;

        for (const phoneData of phoneNumbers) {
            try {
                const { name, phone } = phoneData;
                
                let normalizedPhone = phone.replace(/^\+/, '').replace(/\s/g, '').replace(/-/g, '');
                
                if (!normalizedPhone.startsWith('62') && normalizedPhone.length > 10) {
                    if (normalizedPhone.startsWith('0')) {
                        normalizedPhone = '62' + normalizedPhone.substring(1);
                    }
                } else if (normalizedPhone.length <= 10) {
                    if (normalizedPhone.startsWith('8')) {
                        normalizedPhone = '62' + normalizedPhone;
                    }
                }
                
                console.log(`Checking phone: ${phone} -> normalized: ${normalizedPhone}`);
                
                let foundMatch = false;
                let matchMethod = '';
                let userJid = '';
                
                try {
                    const existenceCheck = await sock.onWhatsApp(normalizedPhone);
                    console.log(`onWhatsApp result for ${normalizedPhone}:`, existenceCheck);
                    
                    if (existenceCheck && existenceCheck.length > 0) {
                        userJid = existenceCheck[0].jid;
                        const userLid = existenceCheck[0].lid;
                        console.log(`User JID from onWhatsApp: ${userJid}`);
                        console.log(`User LID from onWhatsApp: ${userLid}`);
                        
                        for (const participant of participants) {
                            if (participant.id === userJid) {
                                foundMatch = true;
                                matchMethod = 'onWhatsApp JID match';
                                break;
                            }
                        }
                        
                        if (!foundMatch && userLid) {
                            for (const participant of participants) {
                                if (participant.id === userLid) {
                                    foundMatch = true;
                                    matchMethod = 'onWhatsApp LID match';
                                    console.log(`✓ Found LID match: ${userLid} = ${participant.id}`);
                                    break;
                                }
                            }
                        }
                        
                        if (!foundMatch) {
                            const baseJid = userJid.replace('@s.whatsapp.net', '');
                            for (const participant of participants) {
                                if (participant.id.includes(baseJid)) {
                                    foundMatch = true;
                                    matchMethod = 'Partial JID match';
                                    break;
                                }
                            }
                        }
                    } else {
                        console.log(`Phone ${normalizedPhone} is not registered on WhatsApp`);
                    }
                } catch (whatsappError) {
                    console.log(`onWhatsApp check failed for ${normalizedPhone}:`, whatsappError.message);
                    
                    const directJid = normalizedPhone + '@s.whatsapp.net';
                    const lidJid = normalizedPhone + '@lid';
                    
                    for (const participant of participants) {
                        if (participant.id === directJid || participant.id === lidJid) {
                            foundMatch = true;
                            matchMethod = 'Direct JID fallback match';
                            userJid = participant.id;
                            break;
                        }
                    }
                }
                
                if (foundMatch) {
                    results.push({
                        name: name,
                        phone: phone,
                        status: 'Member',
                        reason: `Found in group via ${matchMethod} (JID: ${userJid})`
                    });
                    memberCount++;
                    console.log(`✓ ${name} (${phone}) is a member - ${matchMethod}`);
                } else if (userJid) {
                    results.push({
                        name: name,
                        phone: phone,
                        status: 'Not Member',
                        reason: 'Valid WhatsApp number but not in this group'
                    });
                    notMemberCount++;
                    console.log(`⚠ ${name} (${phone}) is not a member but has valid WhatsApp`);
                } else {
                    results.push({
                        name: name,
                        phone: phone,
                        status: 'Invalid',
                        reason: 'Phone number not registered on WhatsApp'
                    });
                    errorCount++;
                    console.log(`✗ ${name} (${phone}) is invalid - not registered on WhatsApp`);
                }
                
            } catch (error) {
                console.error(`Error checking phone ${phoneData.phone}:`, error);
                results.push({
                    name: phoneData.name,
                    phone: phoneData.phone,
                    status: 'Invalid',
                    reason: `Error: ${error.message}`
                });
                errorCount++;
            }
        }

        console.log(`Check complete: ${memberCount} members, ${notMemberCount} not members, ${errorCount} invalid`);

        res.json({
            success: true,
            results: results,
            members: memberCount,
            notMembers: notMemberCount,
            invalid: errorCount
        });

    } catch (error) {
        console.error('Error checking members:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to check members: ' + error.message
        });
    }
});

app.post('/api/generate-check-log', async (req, res) => {
    try {
        const { logData, groupName } = req.body;

        if (!logData || !Array.isArray(logData)) {
            return res.status(400).json({
                success: false,
                message: 'Invalid log data'
            });
        }

        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet('Member Check Log');

        worksheet.columns = [
            { header: 'Name', key: 'name', width: 30 },
            { header: 'Phone', key: 'phone', width: 20 },
            { header: 'Status', key: 'status', width: 15 },
            { header: 'Reason', key: 'reason', width: 40 },
            { header: 'Timestamp', key: 'timestamp', width: 20 }
        ];

        const headerRow = worksheet.getRow(1);
        headerRow.fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF4F81BD' }
        };
        if (headerRow.font) {
            headerRow.font.color = { argb: 'FFFFFFFF' };
        }
        
        logData.forEach((item, index) => {
            const row = worksheet.addRow({
                name: item.name,
                phone: item.phone,
                status: item.status,
                reason: item.reason || '',
                timestamp: item.timestamp || new Date().toLocaleString('id-ID')
            });
            
            const statusCell = row.getCell('status');
            if (item.status === 'Member') {
                statusCell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFC6EFCE' }
                };
                if (statusCell.font) {
                    statusCell.font.color = { argb: 'FF006100' };
                }
            } else if (item.status === 'Not Member') {
                statusCell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFFFEB9C' }
                };
                if (statusCell.font) {
                    statusCell.font.color = { argb: 'FF9C5700' };
                }
            } else if (item.status === 'Invalid') {
                statusCell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFFFC7CE' }
                };
                if (statusCell.font) {
                    statusCell.font.color = { argb: 'FF9C0006' };
                }
            }
        });
        
        worksheet.addRow([]);
        const summaryRow1 = worksheet.addRow(['Summary Information']);
        summaryRow1.font = { bold: true };
        summaryRow1.getCell(1).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFF2F2F2' }
        };
        
        worksheet.addRow(['Group Name', groupName || 'N/A']);
        worksheet.addRow(['Total Records', logData.length]);
        worksheet.addRow(['Members', logData.filter(item => item.status === 'Member').length]);
        worksheet.addRow(['Not Members', logData.filter(item => item.status === 'Not Member').length]);
        worksheet.addRow(['Invalid', logData.filter(item => item.status === 'Invalid').length]);
        worksheet.addRow(['Generated At', new Date().toLocaleString('id-ID')]);
        
        const logsDir = path.join(__dirname, 'logs');
        if (!fs.existsSync(logsDir)) {
            fs.mkdirSync(logsDir);
        }
        
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').split('T')[0];
        const filename = `member_check_${groupName?.replace(/[^a-zA-Z0-9]/g, '_') || 'group'}_${timestamp}.xlsx`;
        const filepath = path.join(logsDir, filename);
        
        await workbook.xlsx.writeFile(filepath);
        
        res.download(filepath, filename, (err) => {
            if (err) {
                console.error('Error sending file:', err);
                res.status(500).json({
                    success: false,
                    message: 'Error sending file'
                });
            } else {
                setTimeout(() => {
                    fs.unlink(filepath, (unlinkErr) => {
                        if (unlinkErr) console.error('Error deleting file:', unlinkErr);
                    });
                }, 5000);
            }
        });
        
    } catch (error) {
        console.error('Error generating check Excel log:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

server.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
    console.log(`WebSocket server ready for real-time communication`);
});

connectToWhatsApp();
