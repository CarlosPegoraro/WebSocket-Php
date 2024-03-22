const chatWindow = document.getElementById('chat');
const messageInput = document.getElementById('message');
const sendBtn = document.getElementById('sendBtn');

let ws;

function connect() {
    ws = new WebSocket("ws://localhost:8080"); // Change URL if your server runs on a different port

    ws.onopen = function (event) {
        console.log("Connected to server!");
    };

    ws.onmessage = function (event) {
        const data = JSON.parse(event.data);
        chatWindow.innerHTML += `
        <div class='recept-message'>
            <p><b class='from'>${data.from}</b></p>
            <p>${data.msg}</p>
        </div>`;
        chatWindow.scrollTop = chatWindow.scrollHeight; // Scroll to bottom on new message
    };

    ws.onerror = function (error) {
        console.error("Error:", error);
    };

    ws.onclose = function () {
        console.log("Disconnected from server!");
    };
}
document.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
        sendBtn.click();
    }
});

sendBtn.addEventListener('click', function () {
    const message = messageInput.value.trim();
    if (message) {
        chatWindow.innerHTML += `
        <div class='send-message'>
            <p><b class='to'>Você</b></p>
            <p>${message}</p>
        </div>`;
        ws.send(message);
        messageInput.value = "";
    }


});

connect(); // Initiate connection on page load
