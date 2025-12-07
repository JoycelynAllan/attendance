// session_management.js - Session management for faculty and interns

// Create a new session
async function createSession(courseId, courseCode, courseName) {
    const result = await Swal.fire({
        title: 'Create New Session',
        html: `
            <div style="text-align: left;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Course:</label>
                    <input type="text" class="swal2-input" value="${esc(courseCode)} - ${esc(courseName)}" disabled>
                </div>
                <input type="date" id="session_date" class="swal2-input" placeholder="Session Date" required>
                <input type="time" id="start_time" class="swal2-input" placeholder="Start Time" required>
                <input type="time" id="end_time" class="swal2-input" placeholder="End Time" required>
                <input type="text" id="topic" class="swal2-input" placeholder="Topic (Optional)">
                <input type="text" id="location" class="swal2-input" placeholder="Location (Optional)">
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Create Session',
        confirmButtonColor: '#722f37',
        preConfirm: () => {
            const date = document.getElementById('session_date').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            const topic = document.getElementById('topic').value;
            const location = document.getElementById('location').value;
            
            if (!date || !startTime || !endTime) {
                Swal.showValidationMessage('Please fill in all required fields');
                return false;
            }
            
            return {
                course_id: courseId,
                date: date,
                start_time: startTime,
                end_time: endTime,
                topic: topic || null,
                location: location || null
            };
        }
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch('create_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(result.value)
        });
        
        const data = await response.json();
        
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Session Created!',
                html: `
                    <p>Session created successfully!</p>
                    <p><strong>Attendance Code:</strong> <code style="font-size: 20px; padding: 5px 10px; background: #f4f4f4; border-radius: 5px;">${data.attendance_code}</code></p>
                    <p><small>Code expires: ${new Date(data.code_expires_at).toLocaleString()}</small></p>
                `,
                confirmButtonColor: '#722f37'
            });
            
            // Reload sessions
            if (typeof loadSessions === 'function') {
                loadSessions(courseId);
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: data.message,
                confirmButtonColor: '#722f37'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#722f37'
        });
    }
}

// Load sessions for a course
async function loadSessions(courseId) {
    const container = document.getElementById('sessionList');
    if (!container) return;
    
    container.innerHTML = '<p class="loading">Loading sessions...</p>';
    
    try {
        const response = await fetch(`get_sessions.php?course_id=${courseId}`);
        const data = await response.json();
        
        if (data.success && data.sessions && data.sessions.length > 0) {
            container.innerHTML = data.sessions.map(session => {
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
                    <div class="course-item">
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
                    openMarkAttendanceModal(sessionId, courseId);
                });
            });
        } else {
            container.innerHTML = '<p>No sessions created yet. Create your first session!</p>';
        }
    } catch (error) {
        container.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    }
}

// Open mark attendance modal
async function openMarkAttendanceModal(sessionId, courseId) {
    // Load enrolled students
    try {
        const response = await fetch(`get_enrolled_students.php?course_id=${courseId}`);
        const data = await response.json();
        
        if (!data.success || !data.students || data.students.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Students',
                text: 'No enrolled students found for this course.',
                confirmButtonColor: '#722f37'
            });
            return;
        }
        
        // Get current attendance for this session
        const sessionResponse = await fetch(`get_sessions.php?course_id=${courseId}`);
        const sessionData = await sessionResponse.json();
        const session = sessionData.sessions.find(s => s.session_id == sessionId);
        
        // Build student list with attendance status
        const studentList = data.students.map(student => {
            return `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
                    <div>
                        <strong>${esc(student.first_name)} ${esc(student.last_name)}</strong>
                        <br><small>${esc(student.email)}</small>
                    </div>
                    <select class="attendance-status" data-student-id="${student.user_id}" style="padding: 5px;">
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="absent">Absent</option>
                    </select>
                </div>
            `;
        }).join('');
        
        const result = await Swal.fire({
            title: 'Mark Attendance',
            html: `
                <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                    ${studentList}
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Save Attendance',
            confirmButtonColor: '#722f37',
            preConfirm: () => {
                const attendances = [];
                document.querySelectorAll('.attendance-status').forEach(select => {
                    const studentId = select.getAttribute('data-student-id');
                    const status = select.value;
                    attendances.push({ student_id: studentId, status: status });
                });
                return attendances;
            }
        });
        
        if (result.isConfirmed) {
            // Mark attendance for all students
            let successCount = 0;
            let failCount = 0;
            
            for (const attendance of result.value) {
                try {
                    const markResponse = await fetch('mark_attendance.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            session_id: sessionId,
                            student_id: attendance.student_id,
                            status: attendance.status
                        })
                    });
                    
                    const markData = await markResponse.json();
                    if (markData.success) {
                        successCount++;
                    } else {
                        failCount++;
                    }
                } catch (error) {
                    failCount++;
                }
            }
            
            if (failCount === 0) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: `Attendance marked for ${successCount} student(s)`,
                    confirmButtonColor: '#722f37'
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Partial Success',
                    text: `Marked for ${successCount} student(s), ${failCount} failed`,
                    confirmButtonColor: '#722f37'
                });
            }
            
            // Reload sessions
            if (typeof loadSessions === 'function') {
                loadSessions(courseId);
            }
        }
        
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#722f37'
        });
    }
}

function esc(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

