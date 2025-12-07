// faculty_intern_dashboard.js - UPDATED to show assignment status

console.log('=== Faculty Intern Dashboard Script Loaded ===');

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DOM Loaded - Initializing ===');
    setupNavigation();
    loadCourses();
    // Initialize sessions section
    initializeSessions();
});

function setupNavigation() {
    console.log('Setting up navigation...');
    
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.dashboard-section');
    
    console.log('Found:', navLinks.length, 'nav links,', sections.length, 'sections');
    
    navLinks.forEach((link, index) => {
        const sectionName = link.getAttribute('data-section');
        console.log(`  Button ${index}: "${sectionName}"`);
        
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            
            console.log('>>> Button clicked! Section:', section);
            
            // Update buttons
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Update sections
            sections.forEach(s => s.classList.remove('active'));
            
            const targetId = `${section}-section`;
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                targetSection.classList.add('active');
                console.log('  Showing:', targetId);
            } else {
                console.error('  ERROR: Section not found:', targetId);
            }
            
            // Load data
            switch(section) {
                case 'courses': loadCourses(); break;
                case 'sessions': 
                    // Re-initialize sessions when tab is clicked
                    setTimeout(() => {
                        initializeSessions();
                    }, 100);
                    break;
                case 'reports': loadReports(); break;
            }
        });
    });
    
    console.log('Navigation setup complete');
}

async function loadCourses() {
    console.log('=== loadCourses() called ===');
    
    const container = document.getElementById('courseList');
    if (!container) {
        console.error('ERROR: courseList container not found!');
        return;
    }
    
    container.innerHTML = '<p class="loading">Loading courses...</p>';
    
    try {
        const url = 'get_courses.php';
        console.log('Fetching:', url);
        
        const response = await fetch(url);
        console.log('Response status:', response.status);
        
        const text = await response.text();
        console.log('Response (first 300 chars):', text.substring(0, 300));
        
        const data = JSON.parse(text);
        console.log('Parsed data:', data);
        console.log('Number of courses:', data.courses ? data.courses.length : 0);
        
        if (data.success && data.courses && data.courses.length > 0) {
            console.log(`SUCCESS: Displaying ${data.courses.length} courses`);
            
            container.innerHTML = data.courses.map(course => {
                // Determine button state and text
                let buttonHtml = '';
                let assignmentBadge = '';
                
                if (course.is_my_course == 1) {
                    // This intern is already assigned
                    assignmentBadge = '<span class="badge badge-regular" style="margin-top: 10px;">✓ You are assigned to this course</span>';
                    buttonHtml = `<button class="btn btn-sm btn-secondary" disabled>Already Assigned</button>`;
                } else if (course.intern_id && course.intern_id != null) {
                    // Another intern is assigned
                    assignmentBadge = `<span class="badge" style="margin-top: 10px; background: #6c757d; color: white;">Intern: ${esc(course.intern_first_name)} ${esc(course.intern_last_name)}</span>`;
                    buttonHtml = `<button class="btn btn-sm btn-secondary" disabled>Already Has Intern</button>`;
                } else {
                    // No intern assigned - can join
                    buttonHtml = `
                        <button class="btn btn-sm btn-primary join-course-btn" 
                                data-course-id="${course.course_id}"
                                data-course-code="${esc(course.course_code)}">
                            Join as Faculty Intern
                        </button>
                    `;
                }
                
                const clickable = course.is_my_course == 1 ? `onclick="viewCourseStudents(${course.course_id}, '${esc(course.course_code)}', '${esc(course.course_name)}')" style="cursor: pointer;"` : '';
                
                return `
                    <div class="course-item ${course.is_my_course == 1 ? 'my-course' : ''}" ${clickable}>
                        <h4>${esc(course.course_code)} - ${esc(course.course_name)}</h4>
                        <p>${esc(course.description || 'No description provided')}</p>
                        <div class="course-meta">
                            <small><strong>Faculty:</strong> ${esc(course.faculty_first_name)} ${esc(course.faculty_last_name)}</small>
                            <small><strong>Enrolled Students:</strong> ${course.enrolled_count || 0}</small>
                            ${course.credit_hours ? `<small><strong>Credits:</strong> ${course.credit_hours}</small>` : ''}
                        </div>
                        ${assignmentBadge}
                        ${buttonHtml}
                        ${course.is_my_course == 1 ? '<div style="margin-top: 10px; font-size: 12px; color: #722f37;"><em>Click to view enrolled students and attendance</em></div>' : ''}
                    </div>
                `;
            }).join('');
            
            // Attach join buttons
            document.querySelectorAll('.join-course-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const courseId = this.getAttribute('data-course-id');
                    const courseCode = this.getAttribute('data-course-code');
                    console.log('Join button clicked - Course ID:', courseId);
                    joinCourse(courseId, courseCode);
                });
            });
            
        } else {
            console.log('No courses found');
            let message = '<p>No courses available at the moment.</p>';
            
            // Show debug info if available
            if (data.debug) {
                message += `<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; font-size: 12px;">
                    <strong>Debug Info:</strong><br>
                    Database: ${data.debug.database}<br>
                    Total courses in database: ${data.debug.db_info?.total_courses || 'unknown'}<br>
                    Your user ID: ${data.debug.user_id}<br>
                    <em>If courses exist but don't show, check database connection.</em>
                </div>`;
            }
            
            container.innerHTML = message;
            console.warn('No courses found. Debug info:', data.debug || 'none');
        }
    } catch (error) {
        console.error('ERROR in loadCourses:', error);
        console.error('Error stack:', error.stack);
        console.error('Response text (if available):', error.responseText || 'N/A');
        container.innerHTML = `<p style="color: red;">Error loading courses: ${error.message}<br><small>Check browser console (F12) for details</small></p>`;
    }
}

async function joinCourse(courseId, courseCode) {
    console.log('=== joinCourse() called ===');
    console.log('  Course ID:', courseId);
    console.log('  Course Code:', courseCode);
    
    const result = await Swal.fire({
        title: 'Join as Faculty Intern?',
        html: `
            <p>Join <strong>${courseCode}</strong> as Faculty Intern?</p>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">
                You will be assigned to assist with this course and can mark attendance.
            </p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Join',
        confirmButtonColor: '#722f37'
    });
    
    if (!result.isConfirmed) {
        console.log('User cancelled');
        return;
    }
    
    try {
        console.log('Sending join request...');
        
        const response = await fetch('join_course_intern.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_id: parseInt(courseId) })
        });
        
        const text = await response.text();
        console.log('Response:', text);
        
        const data = JSON.parse(text);
        console.log('Parsed:', data);
        
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Assigned!',
                text: data.message,
                timer: 2000
            });
            loadCourses(); // Reload to show updated status
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: data.message,
                confirmButtonColor: '#722f37'
            });
        }
    } catch (error) {
        console.error('ERROR:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#722f37'
        });
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
                if (course.is_my_course == 1) {
                    const option = document.createElement('option');
                    option.value = course.course_id;
                    option.textContent = `${course.course_code} - ${course.course_name}`;
                    courseSelect.appendChild(option);
                }
            });
            
            // Remove any existing event listeners by cloning the select
            const newSelect = courseSelect.cloneNode(true);
            courseSelect.parentNode.replaceChild(newSelect, courseSelect);
            
            // Add new event listener
            newSelect.addEventListener('change', function() {
                const courseId = this.value;
                if (courseId) {
                    const selectedOption = this.options[this.selectedIndex];
                    const courseCode = selectedOption.textContent.split(' - ')[0];
                    const courseName = selectedOption.textContent.split(' - ')[1];
                    
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
                    loadSessionsForCourse(courseId);
                } else {
                    container.innerHTML = '<p>Select a course to view and manage sessions.</p>';
                }
            });
        } else {
            container.innerHTML = '<p>No assigned courses found.</p>';
        }
    } catch (error) {
        container.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    }
}

// Load sessions for a course (renamed to avoid conflict)
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

// View enrolled students with attendance status (same function for intern)
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

function loadReports() {
    console.log('>>> loadReports() called');
    const container = document.getElementById('reportList');
    if (!container) {
        console.error('ERROR: reportList not found');
        return;
    }
    
    container.innerHTML = `
        <p>Reports feature coming soon. You will be able to:</p>
        <ul>
            <li>Create attendance reports</li>
            <li>Create performance reports</li>
            <li>View report history</li>
        </ul>
    `;
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

// Add CSS for assigned courses
const style = document.createElement('style');
style.textContent = `
    .course-item.my-course {
        border-color: #28a745;
        background: #f0fff4;
    }
    
    .course-item.my-course h4 {
        color: #28a745;
    }
`;
document.head.appendChild(style);

console.log('=== Faculty Intern Dashboard Script Complete ===');