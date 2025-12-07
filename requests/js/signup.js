// signup.js - Signup form handler with enhanced debugging

document.addEventListener('DOMContentLoaded', function() {
    console.log('Signup page loaded');
    
    const signupForm = document.getElementById('signupForm');
    
    if (!signupForm) {
        console.error('Signup form not found!');
        return;
    }
    
    signupForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // Get form values
        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const role = document.getElementById('role').value;
        const dob = document.getElementById('dob').value;
        
        console.log('Form values:', {
            firstName, lastName, email, username, 
            role, dob, passwordLength: password.length
        });
        
        // Client-side validation
        if (!firstName || !lastName || !email || !username || !password || !role) {
            console.error('Validation failed: missing fields');
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please fill in all required fields',
                confirmButtonColor: '#722f37'
            });
            return;
        }
        
        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            console.error('Invalid email format');
            Swal.fire({
                icon: 'error',
                title: 'Invalid Email',
                text: 'Please enter a valid email address',
                confirmButtonColor: '#722f37'
            });
            return;
        }
        
        // Validate password length
        if (password.length < 8) {
            console.error('Password too short');
            Swal.fire({
                icon: 'error',
                title: 'Weak Password',
                text: 'Password must be at least 8 characters long',
                confirmButtonColor: '#722f37'
            });
            return;
        }
        
        // Check if passwords match
        if (password !== confirmPassword) {
            console.error('Passwords do not match');
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'Passwords do not match',
                confirmButtonColor: '#722f37'
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'Creating account...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Prepare data
        const formData = {
            first_name: firstName,
            last_name: lastName,
            email: email,
            username: username,
            password: password,
            role: role,
            dob: dob || null
        };
        
        console.log('Sending signup request with data:', {
            ...formData,
            password: '***hidden***'
        });
        
        try {
            const response = await fetch('signup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            // Get response text first to see what we're getting
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('Parsed response data:', data);
            } catch (parseError) {
                console.error('Failed to parse JSON:', parseError);
                console.error('Response was:', responseText);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    html: `<p>The server returned an invalid response.</p>
                           <p><small>Response: ${responseText.substring(0, 200)}</small></p>`,
                    confirmButtonColor: '#722f37'
                });
                return;
            }
            
            if (data.success) {
                console.log('Signup successful!');
                
                // Success
                await Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    text: 'Your account has been created. Redirecting to login...',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                console.log('Redirecting to login page...');
                
                // Redirect to login page
                window.location.href = 'login.html';
            } else {
                console.error('Signup failed:', data.message);
                
                // Error
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: data.message || 'An error occurred during registration',
                    footer: data.error ? `<small>Error: ${data.error}</small>` : '',
                    confirmButtonColor: '#722f37'
                });
            }
        } catch (error) {
            console.error('Network or fetch error:', error);
            
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Could not connect to server. Please check your connection and try again.',
                footer: `<small>${error.message}</small>`,
                confirmButtonColor: '#722f37'
            });
        }
    });
});