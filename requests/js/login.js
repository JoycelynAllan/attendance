// login.js - Login with role-based redirect

document.addEventListener('DOMContentLoaded', function() {
    console.log('Login page loaded');
    
    const loginForm = document.getElementById('loginForm');
    
    if (!loginForm) {
        console.error('Login form not found!');
        return;
    }
    
    // Check for timeout parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('timeout') === '1') {
        Swal.fire({
            icon: 'warning',
            title: 'Session Expired',
            text: 'Your session has expired. Please login again.',
            confirmButtonColor: '#722f37'
        });
    }
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Login form submitted');
        
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        // Client-side validation
        if (!username || !password) {
            console.error('Validation failed: missing fields');
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please fill in all fields',
                confirmButtonColor: '#722f37'
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'Logging in...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        try {
            console.log('Sending login request...');
            
            const response = await fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            });
            
            console.log('Response status:', response.status);
            
            const text = await response.text();
            console.log('Raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
                console.log('Parsed response:', data);
            } catch (parseError) {
                console.error('Failed to parse JSON:', parseError);
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'The server returned an invalid response.',
                    confirmButtonColor: '#722f37'
                });
                return;
            }
            
            if (data.success) {
                console.log('Login successful! Role:', data.role);
                console.log('Redirecting to:', data.dashboard_url);
                
                // Success
                await Swal.fire({
                    icon: 'success',
                    title: 'Login Successful',
                    text: `Welcome back, ${data.first_name}!`,
                    timer: 1500,
                    showConfirmButton: false
                });
                
                // Redirect to role-specific dashboard
                window.location.href = data.dashboard_url;
            } else {
                // Error
                console.error('Login failed:', data.message);
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: data.message || 'Invalid credentials',
                    confirmButtonColor: '#722f37'
                });
            }
        } catch (error) {
            console.error('Login error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred. Please try again.',
                confirmButtonColor: '#722f37'
            });
        }
    });
});