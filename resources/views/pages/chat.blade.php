<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service Chat</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div id="chat-box">
        <div id="chat-output"></div>
        <form id="chat-form">
            <input type="text" id="chat-input" placeholder="Ask something...">
            <button type="submit">Send</button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#chat-form').submit(function(e) {
                e.preventDefault();
                let question = $('#chat-input').val();
                $('#chat-input').val('');  // Kosongkan input field

                // Kirim pertanyaan ke backend untuk mendapatkan jawaban
                $.ajax({
                    url: '/ask',  // URL endpoint Laravel untuk Chat
                    method: 'POST',
                    data: {
                        question: question,
                        _token: '{{ csrf_token() }}'  // Token CSRF untuk keamanan
                    },
                    success: function(response) {
                        // Tampilkan jawaban ChatGPT
                        $('#chat-output').append('<p><strong>You:</strong> ' + question + '</p>');
                        $('#chat-output').append('<p><strong>Bot:</strong> ' + response.answer + '</p>');
                        $('#chat-output').scrollTop($('#chat-output')[0].scrollHeight);  // Scroll ke bawah
                    }
                });
            });
        });
    </script>
</body>
</html>
