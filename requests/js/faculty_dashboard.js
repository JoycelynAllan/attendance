// faculty_dashboard.js - UPDATED with Faculty Intern display

console.log('Faculty dashboard script loaded');

document.addEventListener('DOMContentLoaded', function () {
    console.log('Initializing Faculty Dashboard');
    setupNavigation();
    loadMyCourses();
    loadEnrollmentRequests();
    setupModals();
    // Initialize sessions section on page load
    setTimeout(() => {
        initializeSessions();
    }, 500);
});

function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.dashboard-section');

    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            const sectionName = this.dataset.section;
            console.log('Navigating to:', sectionName);

            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            sections.forEach(s => s.classList.remove('active'));
            const targetSection = document.getElementById(`${sectionName}-section`);
            if (targetSection) targetSection.classList.add('active');

            switch (sectionName) {
                case 'courses': loadMyCourses(); break;
                case 'enrollment': loadEnrollmentRequests(); break;
                case 'sessions': initializeSessions(); break;
                case 'reports': break;
            }
        });
    });
}

function setupModals() {
    const modal = document.getElementById('createCourseModal');
    const btn = document.getElementById('createCourseBtn');
    const span = modal.querySelector('.close');
    const form = document.getElementById('createCourseForm');

    if (btn) btn.onclick = () => modal.style.display = 'block';
    if (span) span.onclick = () => modal.style.display = 'none';

    window.onclick = (e) => {
        if (e.target == modal) modal.style.display = 'none';
    };

    if (form) form.addEventListener('submit', handleCreateCourse);
}

async function loadMyCourses() {
    console.log('Loading faculty courses...');
    const container = document.getElementById('myCourses');
    if (!container) {
        console.error('ERROR: myCourses container not found!');
        return;
    }

    try {
        console.log('Fetching get_courses.php...');
        const response = await fetch('get_courses.php');
        console.log('Response status:', response.status, response.statusText);
        
        const text = await response.text();
        console.log('Response text (first 500 chars):', text.substring(0, 500));
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response text:', text);
            container.innerHTML = `<p style="color: red;">Error: Invalid response from server. Check console for details.<br>Response: ${text.substring(0, 200)}</p>`;
            return;
        }
        
        console.log('Parsed data:', data);
        console.log('Courses count:', data.courses ? data.courses.length : 0);
        
        // Log debug info if available
        if (data.debug) {
            console.warn('DEBUG INFO:', data.debug);
            console.warn('Connected to database:', data.debug.database);
            console.warn('Total courses in database:', data.debug.db_info?.total_courses || 'unknown');
        }

        if (data.success && data.courses && data.courses.length > 0) {
            console.log(`SUCCESS: Displaying ${data.courses.length} courses`);
            container.innerHTML = data.courses.map(course => {
                // Build intern display
                let internInfo = '';
                if (course.intern_first_name && course.intern_last_name) {
                    internInfo = `<small><strong>Faculty Intern:</strong> ${esc(course.intern_first_name)} ${esc(course.intern_last_name)}</small>`;
                } else {
                    internInfo = `<small style="color: #999;"><em>No faculty intern assigned yet</em></small>`;
                }

                return `
                    <div class="course-item" style="cursor: pointer;" onclick="viewCourseStudents(${course.course_id}, '${esc(course.course_code)}', '${esc(course.course_name)}')">
                        <h4>${esc(course.course_code)} - ${esc(course.course_name)}</h4>
                        <p>${esc(course.description || 'No description')}</p>
                        <div class="course-meta">
                            <small><strong>Credit Hours:</strong> ${course.credit_hours || 'N/A'}</small>
                            <small><strong>Enrolled Students:</strong> ${course.enrolled_count || 0}</small>
                            ${course.semester ? `<small><strong>Semester:</strong> ${esc(course.semester)}</small>` : ''}
                            ${internInfo}
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #722f37;">
                            <em>Click to view enrolled students and attendance</em>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            let message = '<p>You have not created any courses yet. Click "Create New Course" to get started!</p>';
            
            // Show debug info if available
            if (data.debug) {
                message += `<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; font-size: 12px;">
                    <strong>Debug Info:</strong><br>
                    Database: ${data.debug.database}<br>
                    Total courses in database: ${data.debug.db_info?.total_courses || 'unknown'}<br>
                    Your user ID: ${data.debug.user_id}<br>
                    <em>If you created courses but they don't show, check if they're in the correct database.</em>
                </div>`;
            }
            
            container.innerHTML = message;
            console.warn('No courses found. Debug info:', data.debug || 'none');
        }
    } catch (error) {
        console.error('ERROR in loadMyCourses:', error);
        console.error('Error stack:', error.stack);
        container.innerHTML = `<p style="color: red;">Error loading courses: ${error.message}<br><small>Check browser console (F12) for details</small></p>`;
    }
}

async function loadEnrollmentRequests() {
    console.log('Loading enrollment requests...');

    try {
        const response = await fetch('manage_enrollment.php');
        const text = await response.text();
        const data = JSON.parse(text);
        const container = document.getElementById('enrollmentRequests');

        if (!container) return;

        if (data.success && data.requests && data.requests.length > 0) {
            container.innerHTML = data.requests.map(request => `
                <div class="request-item">
                    <div class="request-info">
                        <h4>${esc(request.first_name)} ${esc(request.last_name)}</h4>
                        <p><strong>Course:</strong> ${esc(request.course_code)} - ${esc(request.course_name)}</p>
                        <div class="course-meta">
                            <small><strong>Email:</strong> ${esc(request.email)}</small>
                            <small><strong>Username:</strong> ${esc(request.username)}</small>
                            <small><strong>Type:</strong> <span class="badge badge-${request.enrollment_type}">${request.enrollment_type}</span></small>
                            <small><strong>Requested:</strong> ${new Date(request.requested_at).toLocaleString()}</small>
                        </div>
                    </div>
                    <div class="request-actions">
                        <button class="btn btn-sm btn-success approve-btn" data-enrollment-id="${request.enrollment_id}">
                            Approve
                        </button>
                        <button class="btn btn-sm btn-danger reject-btn" data-enrollment-id="${request.enrollment_id}">
                            Reject
                        </button>
                    </div>
                </div>
            `).join('');

            document.querySelectorAll('.approve-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    handleEnrollmentAction(this.getAttribute('data-enrollment-id'), 'approve');
                });
            });

            document.querySelectorAll('.reject-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    handleEnrollmentAction(this.getAttribute('data-enrollment-id'), 'reject');
                });
            });
        } else {
            container.innerHTML = '<p>No pending enrollment requests.</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('enrollmentRequests').innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    }
}

// Initialize sessions section
async function initializeSessions() {
    const container = document.getElementById('sessionList');
    const courseSelect = document.getElementById('sessionCourseSelect');
    
    if (!container) return;
    
    // Load courses for dropdown
    try {
        const response = await fetch('get_courses.php');
        const data = await response.json();
        
        if (data.success && data.courses && data.courses.length > 0) {
            courseSelect.innerHTML = '<option value="">Select a course...</option>';
            data.courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = `${course.course_code} - ${course.course_name}`;
                courseSelect.appendChild(option);
            });
            
            // Remove existing listeners and add new one
            const newSelect = courseSelect.cloneNode(true);
            courseSelect.parentNode.replaceChild(newSelect, courseSelect);
            
            newSelect.addEventListener('change', function() {
                const courseId = this.value;
                console.log('Course selected:', courseId);
                
                if (courseId) {
                    const selectedOption = this.options[this.selectedIndex];
                    const courseCode = selectedOption.textContent.split(' - ')[0];
                    const courseName = selectedOption.textContent.split(' - ')[1];
                    
                    console.log('Setting up session management for:', courseCode, courseName);
                    
                    // Show create button immediately
                    container.innerHTML = `
                        <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <button class="btn btn-primary" onclick="createSession(${courseId}, '${esc(courseCode)}', '${esc(courseName)}')">
                                Create New Session
                            </button>
                            <button class="btn btn-secondary" onclick="viewCourseStudents(${courseId}, '${esc(courseCode)}', '${esc(courseName)}')">
                                View Enrolled Students
                            </button>
                        </div>
                        <div id="sessionsListContainer">
                            <p class="loading">Loading sessions...</p>
                        </div>
                    `;
                    
                    // Load sessions
                    setTimeout(() => {
                        loadSessionsForCourse(courseId);
                    }, 100);
                } else {
                    container.innerHTML = '<p>Select a course to view and manage sessions.</p>';
                }
            });
        } else {
            container.innerHTML = '<p>No courses found. Create a course first!</p>';
        }
    } catch (error) {
        container.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    }
}

// Load sessions for a course (renamed to avoid conflict with session_management.js)
async function loadSessionsForCourse(courseId) {
    const sessionsContainer = document.getElementById('sessionsListContainer');
    if (!sessionsContainer) {
        console.error('sessionsListContainer not found');
        return;
    }
    
    sessionsContainer.innerHTML = '<p class="loading">Loading sessions...</p>';
    
    try {
        const response = await fetch(`get_sessions.php?course_id=${courseId}`);
        const data = await response.json();
        
        if (!data.success) {
            sessionsContainer.innerHTML = `<p style="color: red;">Error: ${data.message || 'Failed to load sessions'}</p>`;
            return;
        }
        
        if (data.sessions && data.sessions.length > 0) {
            sessionsContainer.innerHTML = data.sessions.map(session => {
                const sessionDate = new Date(session.date);
                const formattedDate = sessionDate.toLocaleDateString('en-US', { 
                    weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' 
                });
                
                const codeDisplay = session.attendance_code 
                    ? `<div style="margin-top: 10px;">
                         <strong>Attendance Code:</strong> 
                         <code style="font-size: 16px; padding: 5px 10px; background: #f4f4f4; border-radius: 5px;">${session.attendance_code}</code>
                       </div>`
                    : '';
                
                return `
                    <div class="course-item" style="margin-bottom: 15px;">
                        <h4>${formattedDate} - ${session.start_time} to ${session.end_time}</h4>
                        ${session.topic ? `<p><strong>Topic:</strong> ${esc(session.topic)}</p>` : ''}
                        ${session.location ? `<p><strong>Location:</strong> ${esc(session.location)}</p>` : ''}
                        <div class="course-meta">
                            <small><strong>Course:</strong> ${esc(session.course_code)} - ${esc(session.course_name)}</small>
                            <small><strong>Attendance Count:</strong> ${session.attendance_count || 0}</small>
                        </div>
                        ${codeDisplay}
                        <div style="margin-top: 10px;">
                            <button class="btn btn-sm btn-primary mark-attendance-btn" 
                                    data-session-id="${session.session_id}" 
                                    data-course-id="${session.course_id}">
                                Mark Attendance
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Attach event listeners
            document.querySelectorAll('.mark-attendance-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const sessionId = this.getAttribute('data-session-id');
                    const courseId = this.getAttribute('data-course-id');
                    if (typeof openMarkAttendanceModal === 'function') {
                        openMarkAttendanceModal(sessionId, courseId);
                    }
                });
            });
        } else {
            sessionsContainer.innerHTML = '<p>No sessions created yet. Create your first session using the button above!</p>';
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
        sessionsContainer.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    }
}

// View enrolled students with attendance status
async function viewCourseStudents(courseId, courseCode, courseName) {
    try {
        // Get course details (for faculty and intern info)
        const courseResponse = await fetch('get_courses.php');
        const courseData = await courseResponse.json();
        const course = courseData.courses ? courseData.courses.find(c => c.course_id == courseId) : null;
        
        // Get enrolled students (only students, not faculty/intern)
        const studentsResponse = await fetch(`get_enrolled_students.php?course_id=${courseId}`);
        const studentsData = await studentsResponse.json();
        
        // Get all sessions for this course
        const sessionsResponse = await fetch(`get_sessions.php?course_id=${courseId}`);
        const sessionsData = await sessionsResponse.json();
        
        // Get attendance for all students
        const attendanceData = {};
        if (sessionsData.success && sessionsData.sessions) {
            for (const session of sessionsData.sessions) {
                const attResponse = await fetch(`get_session_attendance.php?session_id=${session.session_id}`);
                const attData = await attResponse.json();
                if (attData.success && attData.attendance) {
                    attData.attendance.forEach(att => {
                        if (!attendanceData[att.student_id]) {
                            attendanceData[att.student_id] = [];
                        }
                        attendanceData[att.student_id].push({
                            session_date: session.date,
                            status: att.status
                        });
                    });
                }
            }
        }
        
        // Build faculty and intern info section
        let facultyInfo = '';
        let internInfo = '';
        
        if (course) {
            if (course.faculty_first_name && course.faculty_last_name) {
                facultyInfo = `
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #722f37;">
                        <h4 style="margin: 0 0 10px 0; color: #722f37;">Faculty</h4>
                        <p style="margin: 5px 0;"><strong>Name:</strong> ${esc(course.faculty_first_name)} ${esc(course.faculty_last_name)}</p>
                        <p style="margin: 5px 0;"><strong>Email:</strong> ${esc(course.faculty_email || 'N/A')}</p>
                    </div>
                `;
            }
            
            if (course.intern_first_name && course.intern_last_name) {
                internInfo = `
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #28a745;">
                        <h4 style="margin: 0 0 10px 0; color: #28a745;">Faculty Intern</h4>
                        <p style="margin: 5px 0;"><strong>Name:</strong> ${esc(course.intern_first_name)} ${esc(course.intern_last_name)}</p>
                        <p style="margin: 5px 0;"><strong>Email:</strong> ${esc(course.intern_email || 'N/A')}</p>
                    </div>
                `;
            }
        }
        
        // Build student list with attendance (only actual students)
        let studentList = '';
        if (studentsData.success && studentsData.students && studentsData.students.length > 0) {
            studentList = studentsData.students.map(student => {
                const studentAttendance = attendanceData[student.user_id] || [];
                const presentCount = studentAttendance.filter(a => a.status === 'present').length;
                const lateCount = studentAttendance.filter(a => a.status === 'late').length;
                const absentCount = studentAttendance.filter(a => a.status === 'absent').length;
                const totalSessions = sessionsData.sessions ? sessionsData.sessions.length : 0;
                const attendancePercentage = totalSessions > 0 ? Math.round((presentCount / totalSessions) * 100) : 0;
                
                return `
                    <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <h4 style="margin: 0 0 5px 0;">${esc(student.first_name)} ${esc(student.last_name)}</h4>
                                <p style="margin: 0; color: #666; font-size: 14px;">${esc(student.email)}</p>
                                <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Username: ${esc(student.username)}</p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 24px; font-weight: bold; color: ${attendancePercentage >= 80 ? 'green' : attendancePercentage >= 60 ? 'orange' : 'red'};">
                                    ${attendancePercentage}%
                                </div>
                                <div style="font-size: 12px; color: #666;">Attendance</div>
                            </div>
                        </div>
                        <div style="margin-top: 10px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center;">
                            <div>
                                <div style="font-weight: bold; color: green;">${presentCount}</div>
                                <div style="font-size: 12px; color: #666;">Present</div>
                            </div>
                            <div>
                                <div style="font-weight: bold; color: orange;">${lateCount}</div>
                                <div style="font-size: 12px; color: #666;">Late</div>
                            </div>
                            <div>
                                <div style="font-weight: bold; color: red;">${absentCount}</div>
                                <div style="font-size: 12px; color: #666;">Absent</div>
                            </div>
                            <div>
                                <div style="font-weight: bold;">${totalSessions}</div>
                                <div style="font-size: 12px; color: #666;">Total</div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            studentList = '<p style="text-align: center; color: #666; padding: 20px;">No enrolled students found for this course.</p>';
        }
        
        await Swal.fire({
            title: `Course Details - ${esc(courseCode)}`,
            html: `
                <div style="max-height: 600px; overflow-y: auto; text-align: left;">
                    ${facultyInfo}
                    ${internInfo}
                    <div style="margin-top: 20px;">
                        <h4 style="margin: 0 0 15px 0; color: #722f37;">Enrolled Students</h4>
                        ${studentList}
                    </div>
                </div>
            `,
            width: '750px',
            confirmButtonText: 'Close',
            confirmButtonColor: '#722f37'
        });
        
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#722f37'
        });
    }
}

async function handleCreateCourse(e) {
    e.preventDefault();
    console.log('Creating course...');

    const formData = {
        course_code: document.getElementById('course_code').value.trim(),
        course_name: document.getElementById('course_name').value.trim(),
        description: document.getElementById('description').value.trim(),
        credit_hours: document.getElementById('credit_hours').value
    };

    try {
        const response = await fetch('create_course.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Course Created!',
                text: data.message,
                timer: 2000
            });

            document.getElementById('createCourseModal').style.display = 'none';
            document.getElementById('createCourseForm').reset();
            await loadMyCourses();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: data.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    }
}

async function handleEnrollmentAction(enrollmentId, action) {
    console.log(`Handling enrollment ${action}...`);

    try {
        const result = await Swal.fire({
            title: `${action.charAt(0).toUpperCase() + action.slice(1)} Request?`,
            text: `Are you sure you want to ${action} this enrollment request?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: action === 'approve' ? '#28a745' : '#dc3545',
            confirmButtonText: `Yes, ${action}`
        });

        if (!result.isConfirmed) return;

        const response = await fetch('manage_enrollment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                enrollment_id: parseInt(enrollmentId),
                action: action
            })
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000
            });
            
            // Reload enrollment requests
            await loadEnrollmentRequests();
            await loadMyCourses();
            
            // Note: The student's pending requests will update when they refresh or navigate to that section
            // The query already filters by status='pending', so approved requests won't show
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: data.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    }
}

function esc(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Make functions globally accessible
window.viewCourseStudents = viewCourseStudents;