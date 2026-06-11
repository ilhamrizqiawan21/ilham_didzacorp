<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Chat Kelas';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$kelas_list = mysqli_query($conn, "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
$selected_kelas = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
?>
<style>
.chat-container {
    height: 500px;
    overflow-y: auto;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.message {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
}
.message-sender {
    font-weight: bold;
    font-size: 0.8rem;
    color: #10b981;
}
.message-time {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-left: 8px;
}
.message-text {
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 18px;
    max-width: 80%;
    word-wrap: break-word;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.message-right {
    align-items: flex-end;
}
.message-right .message-text {
    background: #10b981;
    color: white;
}
.input-group {
    display: flex;
    gap: 8px;
}
.input-group textarea {
    flex: 1;
    border-radius: 24px;
    padding: 10px 16px;
    border: 1px solid #cbd5e1;
    resize: none;
}
.date-separator {
    text-align: center;
    margin: 1rem 0;
    position: relative;
    font-size: 0.75rem;
    color: #64748b;
}
.date-separator span {
    background: #e2e8f0;
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-block;
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-comments"></i> Chat Kelas</h2>
    <p class="page-subtitle">Diskusi interaktif dengan guru dengan siswa</p>
</div>

<div class="form-container">
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Pilih Kelas</label>
            <select name="kelas_id" class="form-select" onchange="this.form.submit()">
                <option value="0">-- Pilih Kelas --</option>
                <?php while($k = mysqli_fetch_assoc($kelas_list)): ?>
                    <option value="<?= $k['id'] ?>" <?= $selected_kelas == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>

<?php if($selected_kelas): ?>
<div class="form-container">
    <div id="chatMessages" class="chat-container">
        <div style="text-align:center; color:#94a3b8;">Memuat pesan...</div>
    </div>
    <div class="input-group">
        <textarea id="messageInput" rows="2" placeholder="Ketik pesan... (Enter untuk kirim, Shift+Enter untuk baris baru)"></textarea>
        <button id="sendBtn" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim</button>
    </div>
</div>

<script>
const kelasId = <?= $selected_kelas ?? $kelas_id ?>;
const userId = <?= $user_id ?>;
let lastMessageId = 0;
let lastMessageDate = ''; // untuk menyimpan tanggal pesan terakhir di container

function formatDateSeparator(dateStr) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const msgDate = new Date(dateStr);
    msgDate.setHours(0, 0, 0, 0);

    if (msgDate.getTime() === today.getTime()) {
        return 'Hari ini';
    } else if (msgDate.getTime() === yesterday.getTime()) {
        return 'Kemarin';
    } else {
        const options = { day: 'numeric', month: 'long', year: 'numeric' };
        return msgDate.toLocaleDateString('id-ID', options);
    }
}

function renderMessages(messages, replace = false) {
    const container = document.getElementById('chatMessages');
    if (replace) {
        container.innerHTML = '';
        lastMessageId = 0;
        lastMessageDate = '';
    }
    if (!messages.length) return;

    let lastDate = lastMessageDate;
    messages.forEach(msg => {
        const msgDate = msg.tanggal; // format YYYY-MM-DD
        if (msgDate !== lastDate) {
            const separator = document.createElement('div');
            separator.className = 'date-separator';
            separator.innerHTML = `<span>${formatDateSeparator(msgDate)}</span>`;
            container.appendChild(separator);
            lastDate = msgDate;
        }
        const isMe = (msg.user_id == userId);
        const div = document.createElement('div');
        div.className = `message ${isMe ? 'message-right' : ''}`;
        div.innerHTML = `
            <div>
                <span class="message-sender">${escapeHtml(msg.nama)}</span>
                <span class="message-time">${msg.time}</span>
            </div>
            <div class="message-text">${escapeHtml(msg.message)}</div>
        `;
        container.appendChild(div);
        lastMessageId = msg.id;
    });
    lastMessageDate = lastDate;
    container.scrollTop = container.scrollHeight;
}

function loadMessages() {
    fetch('../ajax/get_messages?kelas_id=' + kelasId + '&last_id=' + lastMessageId)
        .then(res => res.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                const container = document.getElementById('chatMessages');
                const isFirstLoad = (container.children.length === 1 && container.children[0].innerText === 'Memuat pesan...');
                if (isFirstLoad) {
                    renderMessages(data.messages, true);
                } else {
                    renderMessages(data.messages, false);
                }
            }
        })
        .catch(err => console.error('Gagal memuat pesan:', err));
}

function sendMessage() {
    const message = document.getElementById('messageInput').value.trim();
    if (message === '') return;
    fetch('../ajax/send_message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `kelas_id=${kelasId}&message=${encodeURIComponent(message)}`
    })
    .then(() => {
        document.getElementById('messageInput').value = '';
        loadMessages();
    })
    .catch(err => console.error('Gagal mengirim pesan:', err));
}

function escapeHtml(str) {
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

document.getElementById('sendBtn').addEventListener('click', sendMessage);
document.getElementById('messageInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

setInterval(loadMessages, 3000);
loadMessages();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>