<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Flexavior Chatbot</title>
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style nonce="{{ $cspNonce }}">
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f1f3f5;
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
            background: white;
            box-shadow: 0 0 1rem rgba(0,0,0,0.1);
        }
        
        .chat-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .customer-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .message {
            max-width: 80%;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            line-height: 1.4;
            position: relative;
            word-break: break-word;
        }
        
        .user-message {
            background: var(--primary);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 0.25rem;
        }
        
        .bot-message {
            background: var(--light);
            color: var(--dark);
            align-self: flex-start;
            border-bottom-left-radius: 0.25rem;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
            text-align: right;
        }
        
        .chat-input-area {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            background: white;
        }
        
        .typing-indicator {
            display: inline-flex;
            gap: 0.25rem;
            align-items: center;
        }
        
        .typing-dot {
            width: 0.5rem;
            height: 0.5rem;
            background: var(--gray);
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-0.25rem); }
        }
        
        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 500px;
            padding: 1.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <span>Flexavior Chatbot</span>
            @if(isset($customer_id) || isset($service_number))
                <span class="customer-badge">
                    @if(isset($customer_id)) Customer #{{ $customer_id }} @endif
                    @if(isset($service_number)) | Ticket #{{ $service_number }} @endif
                </span>
            @endif
        </div>
        
        <div class="chat-messages" id="chat-messages"></div>
        
        <div class="chat-input-area">
            <div class="input-group">
                <input 
                    type="text" 
                    id="user-input" 
                    class="form-control" 
                    placeholder="Type your message..." 
                    aria-label="Your message"
                    autocomplete="off"
                >
                <button id="send-button" class="btn btn-primary" type="button">
                    Send
                </button>
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        document.addEventListener('DOMContentLoaded', function() {
            
            const customerId = "{{ $customer_id ?? '' }}";
            const serviceNumber = "{{ $service_number ?? '' }}";
            
            window.chatIdentifiers = {};
            if (customerId) window.chatIdentifiers.customer_id = customerId;
            if (serviceNumber) window.chatIdentifiers.service_number = serviceNumber;
            
            if (customerId || serviceNumber) {
                addMessage(
                    "Hello! I'm your customer support assistant. How can I help you today?",
                    'bot'
                );
            } else {
                showIdentificationModal();
            }
            
            document.getElementById('send-button').addEventListener('click', sendMessage);
            document.getElementById('user-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });
        
        function showIdentificationModal() {
            const modalHTML = `
            <div class="modal-overlay" id="id-modal">
                <div class="modal-content">
                    <h5 class="mb-3">Please verify your information</h5>
                    <div class="mb-3">
                        <label for="customer-id-input" class="form-label">Customer ID</label>
                        <input type="text" id="customer-id-input" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="ticket-input" class="form-label">Service Ticket #</label>
                        <input type="text" id="ticket-input" class="form-control">
                    </div>
                    <button id="confirm-ids" class="btn btn-primary w-100">Continue to Chat</button>
                </div>
            </div>`;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            document.getElementById('confirm-ids').addEventListener('click', function() {
                const customerId = document.getElementById('customer-id-input').value.trim();
                const ticketNumber = document.getElementById('ticket-input').value.trim();
                
                if (customerId && ticketNumber) {
                    window.chatIdentifiers = {
                        customer_id: customerId,
                        service_number: ticketNumber
                    };
                    
                    const badge = document.querySelector('.customer-badge') || 
                        document.querySelector('.chat-header').insertAdjacentHTML('beforeend',
                            '<span class="customer-badge"></span>');
                    
                    document.querySelector('.customer-badge').textContent = 
                        `Customer #${customerId} | Ticket #${ticketNumber}`;
                    
                    document.getElementById('id-modal').remove();
                    
                    addMessage(
                        "Hello! I'm your customer support assistant. How can I help you today?",
                        'bot'
                    );
                } else {
                    alert('Please provide both Customer ID and Service Ticket Number');
                }
            });
        }
        
        function addMessage(content, type) {
            const messagesDiv = document.getElementById('chat-messages');
            const now = new Date();
            const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;
            messageDiv.innerHTML = `
                <div>${content.replace(/\n/g, '<br>')}</div>
                <div class="message-time">${timeString}</div>
            `;
            
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTo({
                top: messagesDiv.scrollHeight,
                behavior: 'smooth'
            });
        }
        
        function showTyping() {
            const messagesDiv = document.getElementById('chat-messages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message bot-message';
            typingDiv.id = 'typing-indicator';
            typingDiv.innerHTML = `
                <div class="typing-indicator">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
                <div class="message-time">typing...</div>
            `;
            
            messagesDiv.appendChild(typingDiv);
            messagesDiv.scrollTo({
                top: messagesDiv.scrollHeight,
                behavior: 'smooth'
            });
        }
        
        function hideTyping() {
            const typing = document.getElementById('typing-indicator');
            if (typing) typing.remove();
        }
        
        async function sendMessage() {
            const input = document.getElementById('user-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            addMessage(message, 'user');
            input.value = '';
            
            showTyping();
            
            try {
                const response = await fetch('/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        input: message,
            		customer_id: window.chatIdentifiers?.customer_id || null,
            		service_number: window.chatIdentifiers?.service_number || null                        
                    })
                });
                
                const data = await response.json();
                hideTyping();
                
                if (data.response) {
                    addMessage(data.response, 'bot');
                } else {
                    addMessage("Sorry, I couldn't process your request. Please try again.", 'bot');
                }
            } catch (error) {
                hideTyping();
                addMessage("Error connecting to the chat service. Please try again later.", 'bot');
                console.error('Chat error:', error);
            }
        }
    </script>
</body>
</html>