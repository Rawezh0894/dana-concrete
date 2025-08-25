<?php
// Simple test file to check API response
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تاقیکردنەوەی API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #ffe6e6; color: #d32f2f; }
        .success { background: #e8f5e9; color: #388e3c; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <h1>تاقیکردنەوەی API</h1>
    
    <button onclick="testAPI()">تاقیکردنەوەی API</button>
    
    <div id="result"></div>
    
    <script>
    function testAPI() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="result">دەست بە تاقیکردنەوە...</div>';
        
        fetch('process/reporst/get_information.php?filter=year')
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.success) {
                        resultDiv.innerHTML = `
                            <div class="result success">
                                <h3>سەرکەوتوو!</h3>
                                <p>داتاکان بە سەرکەوتوویی وەرگیران</p>
                                <h4>داتای سەرەکی:</h4>
                                <pre>${JSON.stringify(data.data, null, 2)}</pre>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="result error">
                                <h3>هەڵە!</h3>
                                <p>${data.error || 'هەڵەیەکی نەناسراو'}</p>
                            </div>
                        `;
                    }
                } catch (e) {
                    resultDiv.innerHTML = `
                        <div class="result error">
                            <h3>هەڵە لە پاڕسکردنی JSON!</h3>
                            <p>هەڵە: ${e.message}</p>
                            <h4>وەڵامی ڕاستەوخۆ:</h4>
                            <pre>${text}</pre>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                resultDiv.innerHTML = `
                    <div class="result error">
                        <h3>هەڵە لە وەرگرتنی زانیاری!</h3>
                        <p>هەڵە: ${error.message}</p>
                    </div>
                `;
            });
    }
    </script>
</body>
</html>
