<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");
require_once("src/security/csrf.php");

// ----------------- ACCESS CONTROL -----------------
$user_q = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` = '".intval($user_id)."' LIMIT 1");
if(mysqli_num_rows($user_q) == 0){
    header("Location: home.php"); exit();
}
$user = mysqli_fetch_assoc($user_q);

// Blocked check
if(trim($user['is_blocked']) === 'true'){
    header("Location: home.php"); exit();
}

// Get product ID
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if($product_id == 0){
    header("Location: home.php"); exit();
}

// Students must have at least 1 set remaining
// Check remaining sets for this user and product
// ----------------- PRODUCT ACCESS CHECK -----------------

$remaining_sets = -1; // default for admin / non-student

if ($user['type'] === 'student') {

    $purchased_q = mysqli_query($conn, "
        SELECT remaining_sets 
        FROM purchased_products 
        WHERE user_id = $user_id 
          AND product_id = $product_id 
          AND status = 'active'
          AND remaining_sets > 0
        LIMIT 1
    ");

    if (mysqli_num_rows($purchased_q) == 0) {
        // Student has NOT purchased this product
        header("Location: home.php");
        exit();
    }

    $purchased = mysqli_fetch_assoc($purchased_q);
    $remaining_sets = (int)$purchased['remaining_sets'];

    if ($remaining_sets < 1) {
        header("Location: home.php");
        exit();
    }
}

// Admin reaches here without any restriction



// Get product name
$product_q = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = $product_id LIMIT 1");
$product = mysqli_fetch_assoc($product_q);
$product_name = $product ? $product['name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Group Discussion</title>
<?php include("src/inc/links.php"); ?>
<style>
/* Container */
.container {
    max-width: 800px;
    margin: 20px auto;
    padding: 0 15px;
}

/* Header */
.chat-header {
    background: var(--primary);
    color: white;
    padding: 12px 20px;
    font-size: 1.4rem;
    font-weight: bold;
    border-radius: 8px 8px 0 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

/* Messages box */
.msg-box {
    height: 70vh;
    overflow-y: auto;
    background: #f9f9f9;
    padding: 15px;
    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    border-radius: 0 0 8px 8px;
}
.msg-box .msg {
    user-select: none;      /* Prevent text selection */
    -webkit-user-select: none;
    -ms-user-select: none;
}

.msg-box .rcv, .msg-box .sent {
    -webkit-touch-callout: none;  /* Prevent long tap context menu on iOS */
}


/* Message bubbles */
.sent .msg, .rcv .msg {
    display: inline-block;
    padding: 10px 15px;
    margin: 6px 0;
    border-radius: 20px;
    max-width: 70%;
    word-wrap: break-word;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    font-size: 0.95rem;
}

/* Sent by me */
.sent {
    text-align: right;
}
.sent .msg {
    background: #dcf8c6;
    color: #000;
}

/* Received */
.rcv {
    text-align: left;
}
.rcv .msg {
    background: #fff;
    border: 1px solid #eee;
    color: #333;
}

/* Form */
#chat-form {
    display: flex;
    margin-top: 10px;
}
#chat-form input[type="text"] {
    flex: 1;
    padding: 10px 15px;
    border-radius: 25px;
    border: 1px solid #ccc;
    outline: none;
    font-size: 0.95rem;
    margin-right: 10px;
}
#chat-form button {
    padding: 10px 20px;
    border: none;
    background: var(--primary);
    color: white;
    font-weight: bold;
    border-radius: 25px;
    cursor: pointer;
    transition: background 0.3s;
}
#chat-form button:hover {
    background: #0056b3;
}

/* Scrollbar styling */
.msg-box::-webkit-scrollbar {
    width: 8px;
}
.msg-box::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.msg-box::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}
.msg-box::-webkit-scrollbar-thumb:hover {
    background: #555;
}
.reply-preview {
    background: #eee;
    padding: 8px 12px;
    border-left: 4px solid var(--primary);
    border-radius: 6px;
    margin-bottom: 6px;
    font-size: 0.85rem;
    position: relative;
}
.reply-preview span {
    position: absolute;
    right: 8px;
    top: 6px;
    cursor: pointer;
    font-weight: bold;
}
.reply-quote {
    font-size: 0.8rem;
    opacity: 0.7;
    border-left: 3px solid #ccc;
    padding-left: 8px;
    margin-bottom: 4px;
}

.msg-options div {
    padding: 10px 16px;
    cursor: pointer;
    font-size: 0.9rem;
}
.msg-options {
    position: fixed; /* was absolute */
    background: #222;
    color: #fff;
    border-radius: 10px;
    overflow: hidden;
    display: none;
    z-index: 9999;
}

.msg-options div:hover {
    background: #444;
}
.msg-pressed {
    background-color: #e0e0e0 !important; /* only the bubble changes */
}
.deleted .msg {
    opacity: 0.6;
    font-style: italic;
}
.deleted-msg {
    color: #777;
}


</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container">
    <div class="chat-header"><?= htmlspecialchars($product_name) ?></div>
    <div class="msg-box" id="msg-box"></div>

    <div id="reply-preview" style="display:none;" class="reply-preview">
        <small>Replying to:</small>
        <div id="reply-text"></div>
        <span onclick="cancelReply()">✕</span>
    </div>


    <form id="chat-form" method="POST">
        <?= csrf_input(); ?>
        <input type="hidden" name="product_id" value="<?= $product_id ?>">
        <input type="hidden" name="reply_to" id="reply_to" value="">
        <input type="text" name="message" class="form-control" required placeholder="Type your message...">
        <button type="submit">Send</button>
    </form>
</div>
<div id="msg-options" class="msg-options">
    <div onclick="chooseReply()">Reply</div>
    <div onclick="chooseReport()">Report</div>
    <div onclick="chooseDelete()" id="delete-option" style="display:none">
        Delete
    </div>
</div>



<script>
let pressedMsg = null;
let pressTimer = null;

/* ---------------- LONG PRESS / RIGHT CLICK ---------------- */
function startPress(el, id, text, username, e){

    // ✅ Clear previous pressed message first
    if (pressedMsg && pressedMsg.msgDiv) {
        pressedMsg.msgDiv.classList.remove('msg-pressed');
        pressedMsg = null;
        $('#msg-options').fadeOut(100); // hide popup if open
    }

    // ✅ MOBILE FIX: if touch is NOT on the bubble, allow scrolling
    if (e.type.startsWith('touch')) {
        const touchedBubble = e.target.closest('.msg');
        if (!touchedBubble) return; // empty area → scroll normally
    }

    // Prevent default ONLY for mouse (desktop)
    if (!e.type.startsWith('touch')) {
        e.preventDefault();
    }

    e.stopPropagation();

    const msgDiv = el.querySelector('.msg');
    msgDiv.classList.add('msg-pressed');

    pressTimer = setTimeout(() => {

        // Correctly detect admin
        const isAdmin = "<?= $type ?>" === "admin";

        // Admin can delete any message; student only own
        const canDelete = isAdmin || el.dataset.canDelete === "1";

        pressedMsg = { id, text, username, msgDiv, canDelete };

        const popup = $('#msg-options');

        // Show or hide Delete button
        if(canDelete){
            $('#delete-option').show();
        } else {
            $('#delete-option').hide();
        }

        let x, y;

        // Determine coordinates for touch vs mouse
        if(e.type.startsWith('touch')) {
            x = e.touches[0].pageX;
            y = e.touches[0].pageY;
        } else {
            x = e.pageX;
            y = e.pageY;
        }

        let popupWidth = popup.outerWidth();
        let popupHeight = popup.outerHeight();
        let windowWidth = $(window).width();
        let windowHeight = $(window).height();

        if(x + popupWidth > windowWidth) x = windowWidth - popupWidth - 10;
        if(y + popupHeight > windowHeight) y = windowHeight - popupHeight - 10;

        popup.css({ top: y + 'px', left: x + 'px' }).fadeIn(100);

    }, 400); // long press 400ms
}


function cancelPress(){
    clearTimeout(pressTimer);
    if (!pressedMsg) {
        document.querySelectorAll('.msg-pressed').forEach(el => el.classList.remove('msg-pressed'));
        $('#msg-options').fadeOut(100);
    }
}


function deleteMessage(msgId, isAdmin = false) {
    let reason = null;

    if (isAdmin) {
        reason = prompt("Delete reason (optional):");
        if (reason === null) return;
    }

    // ✅ auto-detect CSRF hidden field inside the form
    const $csrf = $('#chat-form input[type="hidden"]').filter(function () {
        return this.name.toLowerCase().includes('csrf');
    }).first();

    let payload = {
        action: 'delete',
        msg_id: msgId,
        product_id: <?= (int)$product_id ?>,
        reason: reason
    };

    if ($csrf.length) {
        payload[$csrf.attr('name')] = $csrf.val();
    }

    $.post('chat-actions.php', payload, function(data){
        if (data.success) {
            const box = document.querySelector(`[data-msg-id="${msgId}"]`);
            if (box) {
                box.querySelector('.msg').innerHTML =
                    `<em class="deleted-msg">This message was deleted</em>`;
                box.classList.add('deleted');
            }
        } else {
            alert(data.error || 'Delete failed');
        }
    }, 'json').fail(function(xhr){
        console.log('DELETE failed:', xhr.status, xhr.responseText);
        alert(xhr.responseText || ('Delete request failed (' + xhr.status + ')'));
    });
}


/* ---------------- OPTIONS ---------------- */
function chooseReply(){
    if(!pressedMsg) return;

    $('#reply_to').val(pressedMsg.id);
    $('#reply-text').text(`@${pressedMsg.username}: ${pressedMsg.text}`); // prepend @username

    $('#reply-preview').slideDown();
    $('#msg-options').hide();

    pressedMsg.msgDiv.classList.remove('msg-pressed');
    pressedMsg = null;
}


function chooseReport() {
    if (!pressedMsg) return;

    let reason = prompt("Reason for reporting this message:");
    if (!reason) return; // user cancelled

    console.log('Reporting msg ID:', pressedMsg.id, 'Reason:', reason);

    $.post('chat-report.php', {
        msg_id: pressedMsg.id,
        reason: reason
    }, function(res){
        console.log('Server response:', res);
        if(res.success){
            alert('Message reported successfully.');
        } else {
            alert('Error: ' + (res.error || 'Something went wrong'));
        }
    }, 'json');

    // Clean up
    pressedMsg.msgDiv.classList.remove('msg-pressed');
    pressedMsg = null;
    $('#msg-options').fadeOut(100);
}


function cancelReply(){
    $('#reply_to').val('');
    $('#reply-preview').slideUp();
    if(pressedMsg && pressedMsg.msgDiv){
        pressedMsg.msgDiv.classList.remove('msg-pressed');
    }
    pressedMsg = null;
}

/* ---------------- CLICK OUTSIDE ---------------- */
$(document).on('click touchstart', function(e){
    const popup = $('#msg-options')[0];
    if (pressedMsg && !popup.contains(e.target) && !pressedMsg.msgDiv.contains(e.target)) {
        // Clicked outside the message & menu -> cancel highlight
        pressedMsg.msgDiv.classList.remove('msg-pressed');
        pressedMsg = null;
        $('#msg-options').fadeOut(100);
    }
});

/* ---------------- DESKTOP RIGHT-CLICK ON MESSAGE ---------------- */
$('.msg-box').on('contextmenu', '.rcv, .sent', function(e){
    e.preventDefault();
    e.stopPropagation();

    const containerDiv = this;
    const msgDiv = containerDiv.querySelector('.msg');

    const msgId = containerDiv.dataset.msgId;
    const msgText = containerDiv.dataset.msgText;
    const msgUsername = containerDiv.dataset.msgUsername;
    const canDelete = containerDiv.dataset.canDelete === "1";

    // Remove existing highlights
    document.querySelectorAll('.msg-pressed')
        .forEach(el => el.classList.remove('msg-pressed'));

    // Highlight bubble
    msgDiv.classList.add('msg-pressed');

    pressedMsg = {
        id: msgId,
        text: msgText,
        username: msgUsername,
        msgDiv,
        canDelete
    };

    // DELETE VISIBILITY (THIS WAS MISSING)
    const isAdmin = "<?= $user['type'] ?>" === "admin";

    if (isAdmin || canDelete) {
        $('#delete-option').show();
    } else {
        $('#delete-option').hide();
    }

    // Popup positioning
    const popup = $('#msg-options');

    let x = e.pageX;
    let y = e.pageY;

    const pw = popup.outerWidth();
    const ph = popup.outerHeight();

    if (x + pw > window.innerWidth) x -= pw + 10;
    if (y + ph > window.innerHeight) y -= ph + 10;

    popup.css({ top: y, left: x }).fadeIn(100);
});




/* ---------------- LOAD MESSAGES ---------------- */
let userAtBottom = true;

function escapeHtml(text) {
    return $('<div>').text(text).html();
}

function loadMessages() {
    const box = $('#msg-box')[0];

    // 🔹 Check if user is near bottom BEFORE updating
    userAtBottom = (box.scrollHeight - box.scrollTop - box.clientHeight) < 50;

    $.getJSON('chat-actions.php', {
        action: 'fetch',
        product_id: <?= (int)$product_id ?>
    }, function(data) {

        let html = '';

        data.forEach(msg => {

            const safeMessage   = escapeHtml(msg.message || '');
            const safeUsername  = escapeHtml(msg.username || '');
            const safeReplyMsg  = msg.reply_message ? escapeHtml(msg.reply_message) : '';
            const safeReplyUser = msg.reply_username ? escapeHtml(msg.reply_username) : '';

            /* ---------------- DELETED MESSAGE ---------------- */
            if (msg.is_deleted == 1) {
                html += `
                <div class="${msg.sent_by_user ? 'sent' : 'rcv'} deleted"
                     data-msg-id="${msg.id}"
                     data-msg-text="${escapeHtml(msg.message)}"
                     data-msg-username="${escapeHtml(msg.username)}"
                     data-can-delete="${msg.can_delete ? 1 : 0}"
                     onmousedown="startPress(this, ${msg.id}, this.dataset.msgText, this.dataset.msgUsername, event)"
                     onmouseup="cancelPress()"
                     ontouchstart="startPress(this, ${msg.id}, this.dataset.msgText, this.dataset.msgUsername, event)"
                     ontouchend="cancelPress()">
            
                    <div class="msg">
                        <strong>@${escapeHtml(msg.username)}:</strong> 
                        <em class="deleted-msg">This message was deleted</em>
                        ${
                            msg.deleted_reason
                            ? `<div class="reply-quote"><strong>Reason:</strong> ${escapeHtml(msg.deleted_reason)}</div>`
                            : ''
                        }
                    </div>
                </div>`;
                return;
            }


            /* ---------------- NORMAL MESSAGE ---------------- */
            html += `
            <div class="${msg.sent_by_user ? 'sent':'rcv'}"
                 data-msg-id="${msg.id}"
                 data-msg-text="${safeMessage}"
                 data-msg-username="${safeUsername}"
                 data-can-delete="${msg.can_delete ? 1 : 0}"
                 onmousedown="startPress(this, ${msg.id}, this.dataset.msgText, this.dataset.msgUsername, event)"
                 onmouseup="cancelPress()"
                 ontouchstart="startPress(this, ${msg.id}, this.dataset.msgText, this.dataset.msgUsername, event)"
                 ontouchend="cancelPress()">

                <div class="msg">
                    ${safeReplyMsg ? `<div class="reply-quote">@${safeReplyUser}: ${safeReplyMsg}</div>` : ''}
                    <strong>@${safeUsername}:</strong> ${safeMessage}
                </div>
            </div>`;
        });

        $('#msg-box').html(html);

        // 🔹 Auto-scroll ONLY if user was at bottom
        if (userAtBottom) {
            box.scrollTop = box.scrollHeight;
        }
    });
}

/* ---------------- SEND MESSAGE ---------------- */
$('#chat-form').on('submit', function(e){
    e.preventDefault();

    $.post('chat-actions.php', $(this).serialize(), function(res){
        if(res.success){
            $('input[name="message"]').val('');
            cancelReply();
            loadMessages();
        } else {
            alert(res.error || 'Failed to send');
        }
    }, 'json');
});

/* ---------------- AUTO REFRESH ---------------- */
setInterval(loadMessages, 3000);
loadMessages();

// ---------------- GLOBAL DELETE FUNCTION ----------------
function chooseDelete() {
    if (!pressedMsg) return;

    const isAdmin = "<?= $user['type'] ?>" === "admin";
    deleteMessage(pressedMsg.id, isAdmin);

    pressedMsg.msgDiv.classList.remove('msg-pressed');
    pressedMsg = null;
    $('#msg-options').fadeOut(100);
}


</script>




<?php include("src/inc/footer.php"); ?>
</body>
</html>
