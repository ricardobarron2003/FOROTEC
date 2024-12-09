<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos personalizados */
        body {
            background-color: #f8f9fa;
        }
        
        .chatbot-card {
            width: 100%;
            max-width: 400px;
            height: 600px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
        }
        
        .chat-area {
            overflow-y: auto;
            height: 100%;
            padding: 20px;
            background-color: #ffffff;
        }
        
        .chat-message {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .bot-message .avatar img,
        .user-message .avatar img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .bot-message .avatar {
            margin-right: 10px;
        }
        
        .user-message .avatar {
            order: 2;
            margin-left: 10px;
        }
        
        .bot-message .message,
        .user-message .message {
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 70%;
        }
        
        .bot-message .message {
            background-color: #e9ecef;
            color: #333;
        }
        
        .user-message .message {
            background-color: #007bff;
            color: #fff;
        }
        
        .card-footer {
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card chatbot-card">
            <div class="card-header text-center bg-primary text-white">
                <h4>Chatbot</h4>
            </div>
            <div class="card-body chat-area" id="chat-area">
                <!-- Mensajes del chatbot y del usuario (solo ejemplos) -->
                <div class="chat-message bot-message">
                    <div class="avatar"><img src="bot_avatar.png" alt="Bot"></div>
                    <div class="message">Hola, ¿en qué puedo ayudarte?</div>
                </div>
                <div class="chat-message user-message">
                    <div class="avatar"><img src="user_avatar.png" alt="Usuario"></div>
                    <div class="message">Quisiera saber más sobre el foro escolar.</div>
                </div>
            </div>
            <div class="card-footer">
                <form id="chat-form" class="d-flex">
                    <input type="text" class="form-control" placeholder="Escribe un mensaje..." required>
                    <button type="submit" class="btn btn-primary ms-2">Enviar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (necesario para la funcionalidad de algunos elementos) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
