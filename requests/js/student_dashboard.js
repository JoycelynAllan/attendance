// student_dashboard.js - FIXED to show instructor, intern, and enrollment type

console.log('=== Student Dashboard Script Loaded ===');

document.addEventListener('DOMContentLoaded', function () {
    console.log('=== DOM Loaded ===');
    setupNavigation();
    loadEnrolledCourses();
    setupJoinModal();

    // Auto-refresh pending requests every 30 seconds when that section is active
    setInterval(() => {
        const pendingSection = document.getElementById('pending-section');
        if (pendingSection && pendingSection.classList.contains('active')) {
            console.log('Auto-refreshing pending requests...');
            loadPendingRequests();
        }
    }, 30000); // Refresh every 30 seconds
});

function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.dashboard-section');

    console.log('Navigation setup - Links:', navLinks.length, 'Sections:', sections.length);

    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            const sectionName = this.dataset.section;
            console.log('>>> Navigate to:', sectionName);

            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            sections.forEach(s => s.classList.remove('active'));
            const targetSection = document.getElementById(`${sectionName}-section`);
            if (targetSection) {
                targetSection.classList.add('active');
                console.log('Showing section:', targetSection.id);
            }

            switch (sectionName) {
                case 'courses': loadEnrolledCourses(); break;
                case 'available': loadAvailableCourses(); break;
                case 'pending':
                    loadPendingRequests();
                    // Also refresh enrolled courses in case a request was just approved
                    setTimeout(() => loadEnrolledCourses(), 500);
                    break;
                case 'schedule': loadSchedule(); break;
                case 'grades': loadGrades(); break;
            }
        });
    });
}

async function loadEnrolledCourses() {
    console.log('>>> loadEnrolledCourses() called');
    const container = document.getElementById('enrolledCourses');
    if (!container) {
        console.error('ERROR: enrolledCourses container not found!');
        return;
    }

    container.innerHTML = '<p class="loading">Loading courses...</p>';

    try {
        const url = 'get_courses.php?type=enrolled';
        console.log('Fetching:', url);

        const response = await fetch(url);
        console.log('Response status:', response.status, response.statusText);

        const text = await response.text();
        console.log('Response text (first 300 chars):', text.substring(0, 300));

        const data = JSON.parse(text);
        console.log('Parsed data:', data);

        if (data.success && data.courses && data.courses.length > 0) {
            console.log(`SUCCESS: Found ${data.courses.length} enrolled courses`);
            container.innerHTML = data.courses.map(course => {
                // Build instructor info
                let instructorInfo = '';
                if (course.faculty_first_name && course.faculty_last_name) {
                    instructorInfo = `<small><strong>Instructor:</strong> ${esc(course.faculty_first_name)} ${esc(course.faculty_last_name)}</small>`;
                }

                // Build intern info
                let internInfo = '';
                if (course.intern_first_name && course.intern_last_name) {
                    internInfo = `<small><strong>Faculty Intern:</strong> ${esc(course.intern_first_name)} ${esc(course.intern_last_name)}</small>`;
                }

                // Build credit hours
                let creditInfo = '';
                if (course.credit_hours) {
                    creditInfo = `<small><strong>Credits:</strong> ${course.credit_hours}</small>`;
                }

                return `
                    <div class="course-item">
                        <h4>${esc(course.course_code)} - ${esc(course.course_name)}</h4>
                        <p>${esc(course.description || 'No description')}</p>
                        <div class="course-meta">
                            ${instructorInfo}
                            ${internInfo}
                            ${creditInfo}
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            console.log('No enrolled courses found');
            let message = '<p>You are not enrolled in any courses yet. Check "Available Courses" to join!</p>';

            // Show debug info if available
            if (data.debug) {
                message += `<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; font-size: 12px;">
                    <strong>Debug Info:</strong><br>
                    Database: ${data.debug.database}<br>
                    Total courses in database: ${data.debug.db_info?.total_courses || 'unknown'}<br>
                    Your user ID: ${data.debug.user_id}<br>
                    <em>If you're enrolled but courses don't show, check database connection.</em>
                </div>`;
            }

            container.innerHTML = message;
            console.warn('No enrolled courses found. Debug info:', data.debug || 'none');
        }
    } catch (error) {
        console.error('ERROR in loadEnrolledCourses:', error);
        console.error('Error stack:', error.stack);
        container.innerHTML = `<p style="color:red;">Error loading courses: ${error.message}<br><small>Check browser console (F12) for details</small></p>`;
    }
}

async function loadAvailableCourses() {
    console.log('>>> loadAvailableCourses() called');
    const container = document.getElementById('availableCourses');
    if (!container) {
        console.error('ERROR: availableCourses container not found!');
        return;
    }

    container.innerHTML = '<p class="loading">Loading courses...</p>';

    try {
        const url = 'get_courses.php?type=available';
        console.log('Fetching:', url);

        const response = await fetch(url);
        console.log('Response status:', response.status);

        const text = await response.text();
        console.log('Response text (first 300 chars):', text.substring(0, 300));

        const data = JSON.parse(text);
        console.log('Parsed data:', data);
        console.log('Number of courses:', data.courses ? data.courses.length : 0);

        if (data.success && data.courses && data.courses.length > 0) {
            console.log(`SUCCESS: Found ${data.courses.length} available courses`);

            // Log each course ID
            data.courses.forEach((course, index) => {
                console.log(`Course ${index}: ID=${course.course_id}, Code=${course.course_code}`);
            });

            container.innerHTML = data.courses.map(course => {
                // Build instructor info
                let instructorInfo = '';
                if (course.faculty_first_name && course.faculty_last_name) {
                    instructorInfo = `<small><strong>Instructor:</strong> ${esc(course.faculty_first_name)} ${esc(course.faculty_last_name)}</small>`;
                }

                // Build intern info
                let internInfo = '';
                if (course.intern_first_name && course.intern_last_name) {
                    internInfo = `<small><strong>Faculty Intern:</strong> ${esc(course.intern_first_name)} ${esc(course.intern_last_name)}</small>`;
                }

                // Build credit hours
                let creditInfo = '';
                if (course.credit_hours) {
                    creditInfo = `<small><strong>Credits:</strong> ${course.credit_hours}</small>`;
                }

                return `
                    <div class="course-item">
                        <h4>${esc(course.course_code)} - ${esc(course.course_name)}</h4>
                        <p>${esc(course.description || 'No description')}</p>
                        <div class="course-meta">
                            ${instructorInfo}
                            ${internInfo}
                            ${creditInfo}
                        </div>
                        <button class="btn btn-sm btn-primary join-course-btn" 
                                data-course-id="${course.course_id}"
                                data-course-code="${esc(course.course_code)}"
                                data-course-name="${esc(course.course_name)}">
                            Request to Join
                        </button>
                    </div>
                `;
            }).join('');

            // Attach event listeners
            const buttons = document.querySelectorAll('.join-course-btn');
            console.log('Attaching listeners to', buttons.length, 'buttons');

            buttons.forEach((btn, index) => {
                const courseId = btn.getAttribute('data-course-id');
                console.log(`Button ${index}: Course ID = ${courseId}`);

                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const cId = this.getAttribute('data-course-id');
                    const cCode = this.getAttribute('data-course-code');
                    const cName = this.getAttribute('data-course-name');

                    console.log('>>> Join button clicked!');
                    console.log('  Course ID:', cId);
                    console.log('  Course Code:', cCode);
                    console.log('  Course Name:', cName);

                    // DEBUG: Show ID to user
                    // alert('Debug: Joining Course ID: ' + cId);

                    if (!cId) {
                        alert('Error: Course ID is missing from the button. Please refresh the page.');
                        return;
                    }

                    openJoinModal(cId, cCode, cName);
                });
            });
        } else {
            console.log('No available courses found');
            let message = '<p>No available courses at the moment.</p>';

            // Show debug info if available
            if (data.debug) {
                message += `<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; font-size: 12px;">
                    <strong>Debug Info:</strong><br>
                    Database: ${data.debug.database}<br>
                    Total courses in database: ${data.debug.db_info?.total_courses || 'unknown'}<br>
                    <em>If courses exist but don't show, check database connection.</em>
                </div>`;
            }

            container.innerHTML = message;
            console.warn('No available courses found. Debug info:', data.debug || 'none');
        }
    } catch (error) {
        console.error('ERROR in loadAvailableCourses:', error);
        console.error('Error stack:', error.stack);
        container.innerHTML = `<p style="color:red;">Error loading courses: ${error.message}<br><small>Check browser console (F12) for details</small></p>`;
    }
}

async function loadPendingRequests() {
    console.log('>>> loadPendingRequests() called');
    const container = document.getElementById('pendingRequests');
    if (!container) return;

    container.innerHTML = '<p class="loading">Loading...</p>';

    try {
        const url = 'get_courses.php?type=pending';
        console.log('Fetching:', url);

        const response = await fetch(url);
        const text = await response.text();
        console.log('Pending response:', text.substring(0, 200));

        const data = JSON.parse(text);
        console.log('Pending data:', data);

        if (data.success && data.courses && data.courses.length > 0) {
            console.log(`Found ${data.courses.length} pending requests`);
            container.innerHTML = data.courses.map(course => {
                // Build instructor info
                let instructorInfo = '';
                if (course.faculty_first_name && course.faculty_last_name) {
                    instructorInfo = `<small><strong>Instructor:</strong> ${esc(course.faculty_first_name)} ${esc(course.faculty_last_name)}</small>`;
                }

                // Build intern info
                let internInfo = '';
                if (course.intern_first_name && course.intern_last_name) {
                    internInfo = `<small><strong>Faculty Intern:</strong> ${esc(course.intern_first_name)} ${esc(course.intern_last_name)}</small>`;
                }

                // Get enrollment type badge
                let typeBadge = '';
                if (course.enrollment_type) {
                    typeBadge = `<span class="badge badge-${course.enrollment_type}">${course.enrollment_type}</span>`;
                }

                return `
                    <div class="course-item pending">
                        <h4>${esc(course.course_code)} - ${esc(course.course_name)}</h4>
                        <p>${esc(course.description || 'No description')}</p>
                        <div class="course-meta">
                            ${instructorInfo}
                            ${internInfo}
                            <small><strong>Requested as:</strong> ${typeBadge}</small>
                            <small><strong>Requested:</strong> ${new Date(course.requested_at).toLocaleString()}</small>
                        </div>
                        <span class="badge badge-warning">⏳ Pending Approval</span>
                    </div>
                `;
            }).join('');
        } else {
            console.log('No pending requests');
            container.innerHTML = '<p>No pending requests.</p>';
        }
    } catch (error) {
        console.error('ERROR in loadPendingRequests:', error);
        container.innerHTML = `<p style="color:red;">Error: ${error.message}</p>`;
    }
}

function loadSchedule() {
    console.log('>>> loadSchedule() called');
    // This is now handled by the attendance check-in section
    const container = document.getElementById('checkInSection');
    if (container) {
        container.innerHTML = `
            <p>Use the attendance code provided by your instructor to check in for a session.</p>
            <button class="btn btn-primary" onclick="checkInWithCode()" style="margin-top: 15px;">
                Check In with Code
            </button>
        `;
    }
}

function loadGrades() {
    console.log('>>> loadGrades() called');
    document.getElementById('gradesList').innerHTML = '<p>Grades coming soon...</p>';
    document.getElementById('feedbackList').innerHTML = '<p>Feedback coming soon...</p>';
}

function setupJoinModal() {
    console.log('>>> setupJoinModal() called');
    const modal = document.getElementById('joinCourseModal');
    const closeBtn = modal.querySelector('.close');
    const form = document.getElementById('joinCourseForm');

    console.log('Modal elements:', {
        modal: !!modal,
        closeBtn: !!closeBtn,
        form: !!form
    });

    if (closeBtn) {
        closeBtn.onclick = () => {
            console.log('Close button clicked');
            modal.style.display = 'none';
        };
    }

    window.onclick = (e) => {
        if (e.target === modal) {
            console.log('Clicked outside modal');
            modal.style.display = 'none';
        }
    };

    if (form) {
        form.onsubmit = handleJoinSubmit;
        console.log('Form submit handler attached');
    }
}

function openJoinModal(courseId, courseCode, courseName) {
    console.log('==========================================================');
    console.log('>>> openJoinModal() called');
    console.log('  Parameters received:');
    console.log('    courseId:', courseId, 'Type:', typeof courseId);
    console.log('    courseCode:', courseCode);
    console.log('    courseName:', courseName);

    const modal = document.getElementById('joinCourseModal');
    const hiddenInput = document.getElementById('selected_course_id');
    const courseInfo = document.getElementById('courseInfo');

    console.log('  Modal elements found:', {
        modal: !!modal,
        hiddenInput: !!hiddenInput,
        courseInfo: !!courseInfo
    });

    if (!hiddenInput) {
        console.error('CRITICAL ERROR: Hidden input field not found!');
        alert('Error: Form field missing. Please refresh the page.');
        return;
    }

    // Set hidden input value
    hiddenInput.value = courseId;
    console.log('  Hidden input value SET TO:', hiddenInput.value);
    console.log('  Verifying: hiddenInput.value =', hiddenInput.value);

    // Update course info
    if (courseInfo) {
        courseInfo.innerHTML = `
            <div class="course-info-display">
                <h3>${esc(courseCode)} - ${esc(courseName)}</h3>
                <p>Select how you would like to join:</p>
            </div>
        `;
    }

    // Show modal
    modal.style.display = 'block';
    console.log('  Modal displayed');
    console.log('==========================================================');
}

async function handleJoinSubmit(e) {
    e.preventDefault();
    console.log('==========================================================');
    console.log('>>> handleJoinSubmit() called - FORM SUBMITTED');

    const hiddenInput = document.getElementById('selected_course_id');
    const courseId = hiddenInput ? hiddenInput.value : null;

    console.log('  Hidden input element:', hiddenInput);
    console.log('  Course ID from hidden input:', courseId);
    console.log('  Course ID type:', typeof courseId);
    console.log('  Course ID length:', courseId ? courseId.length : 0);
    console.log('  Course ID is empty?', !courseId || courseId === '');

    if (!courseId || courseId === '' || courseId === 'null' || courseId === 'undefined') {
        console.error('CRITICAL ERROR: Course ID is invalid!');
        console.error('  Value:', courseId);
        console.error('  This should NOT happen if button was clicked correctly');

        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `
                <p>Course ID is missing.</p>
                <p><small>Value: "${courseId}"</small></p>
                <p>Please close this dialog and try clicking the button again.</p>
            `,
            confirmButtonColor: '#722f37'
        });

        // DEBUG: Help user find the issue
        alert('Debug: Hidden Input = ' + (hiddenInput ? 'Found' : 'Missing') +
            ', Value = "' + (hiddenInput ? hiddenInput.value : 'N/A') + '"');

        return;
    }

    const enrollmentType = document.querySelector('input[name="enrollment_type"]:checked');
    if (!enrollmentType) {
        console.error('ERROR: No enrollment type selected');
        Swal.fire({
            icon: 'warning',
            title: 'Selection Required',
            text: 'Please select an enrollment type (Regular, Auditor, or Observer)',
            confirmButtonColor: '#722f37'
        });
        return;
    }

    const enrollmentValue = enrollmentType.value;
    console.log('  Enrollment type:', enrollmentValue);

    const requestData = {
        course_id: parseInt(courseId),
        enrollment_type: enrollmentValue
    };

    console.log('  Request data to send:', JSON.stringify(requestData, null, 2));

    try {
        console.log('  Sending POST request to join_course.php...');

        const response = await fetch('join_course.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });

        console.log('  Response received:');
        console.log('    Status:', response.status, response.statusText);
        console.log('    Headers:', response.headers);

        const text = await response.text();
        console.log('  Response text:', text);

        let data;
        try {
            data = JSON.parse(text);
            console.log('  Parsed response:', data);
        } catch (parseError) {
            console.error('  ERROR: Failed to parse JSON');
            console.error('  Parse error:', parseError);
            console.error('  Response was:', text);
            throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
        }

        if (data.success) {
            console.log('  SUCCESS! Request submitted successfully');

            await Swal.fire({
                icon: 'success',
                title: 'Request Submitted!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });

            // Close modal
            document.getElementById('joinCourseModal').style.display = 'none';

            // Clear hidden input
            hiddenInput.value = '';

            // Reload data
            console.log('  Reloading available courses and pending requests...');
            await loadAvailableCourses();
            await loadPendingRequests();
            // Also refresh enrolled courses in case this was just approved
            setTimeout(() => loadEnrolledCourses(), 500);

        } else {
            console.error('  FAILED:', data.message);

            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: data.message,
                confirmButtonColor: '#722f37'
            });
        }
    } catch (error) {
        console.error('=== ERROR in handleJoinSubmit ===');
        console.error('Error:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);

        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `
                <p>Failed to submit request</p>
                <p><small>${error.message}</small></p>
            `,
            confirmButtonColor: '#722f37'
        });
    }

    console.log('==========================================================');
}

function esc(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

console.log('=== Student Dashboard Script Initialization Complete ===');