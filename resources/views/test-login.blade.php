<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test Login</title>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body>
    <div x-data="testLogin()" class="p-8">
        <h1>Test Login</h1>
        
        <div class="mb-4">
            <label>Email:</label>
            <input type="email" x-model="email" class="border p-2" value="miguel.orozco@me.com">
        </div>
        
        <div class="mb-4">
            <label>Password:</label>
            <input type="password" x-model="password" class="border p-2" value="password123">
        </div>
        
        <button @click="testLoginAPI()" :disabled="loading" class="bg-blue-500 text-white p-2 rounded">
            <span x-text="loading ? 'Testing...' : 'Test Login'"></span>
        </button>
        
        <div x-show="result" class="mt-4 p-4 bg-gray-100">
            <h3>Result:</h3>
            <pre x-text="result"></pre>
        </div>
        
        <div x-show="error" class="mt-4 p-4 bg-red-100 text-red-800">
            <h3>Error:</h3>
            <pre x-text="error"></pre>
        </div>
    </div>

    <script>
        function testLogin() {
            return {
                email: 'miguel.orozco@me.com',
                password: 'password123',
                loading: false,
                result: null,
                error: null,
                
                async testLoginAPI() {
                    this.loading = true;
                    this.result = null;
                    this.error = null;
                    
                    try {
                        console.log('Starting login test...');
                        console.log('Email:', this.email);
                        console.log('Password:', this.password);
                        
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        console.log('CSRF Token:', csrfToken);
                        
                        const response = await fetch('/api/auth/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                email: this.email,
                                password: this.password
                            })
                        });
                        
                        console.log('Response status:', response.status);
                        console.log('Response headers:', [...response.headers.entries()]);
                        
                        const data = await response.json();
                        console.log('Response data:', data);
                        
                        if (response.ok) {
                            this.result = JSON.stringify(data, null, 2);
                            
                            if (data.success) {
                                // Test redirect
                                console.log('Login successful, testing redirect...');
                                setTimeout(() => {
                                    window.location.href = '/dashboard';
                                }, 2000);
                            }
                        } else {
                            this.error = JSON.stringify(data, null, 2);
                        }
                        
                    } catch (err) {
                        console.error('Error:', err);
                        this.error = err.message;
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
