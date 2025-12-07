// student_attendance.js - Student attendance features

// Check in using attendance code
async function checkInWithCode() {
    const result = await Swal.fire({
        title: 'Check In with Code',
        input: 'text',
        inputLabel: 'Enter Attendance Code',
        inputPlaceholder: '6-digit code',
        inputAttributes: {
            maxlength: 6,
            pattern: '[0-9]{6}',
            style: 'text-align: center; font-size: 24px; letter-spacing: 5px;'
        },
        showCancelButton: true,
        confirmButtonText: 'Check In',
        confirmButtonColor: '#722f37',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter the attendance code';
            }
            if (!/^\d{6}$/.test(value)) {
                return 'Code must be 6 digits';
            }
        }
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch('check_in_code.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: result.value })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Checked In!',
                html: `
                    <p>${data.message}</p>
                    <p><strong>Course:</strong> ${esc(data.course)}</p>
                    <p><strong>Date:</strong> ${new Date(data.session_date).toLocaleDateString()}</p>
                    <p><strong>Status:</strong> <span style="text-transform: capitalize;">${data.status}</span></p>
                `,
                confirmButtonColor: '#722f37'
            });
            
            // Reload attendance reports if on that section
            if (typeof loadAttendanceReports === 'function') {
                const courseId = getCurrentCourseId();
                if (courseId) {
                    loadAttendanceReports(courseId);
                }
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Check In Failed',
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

// Load attendance reports
async function loadAttendanceReports(courseId, type = 'overall') {
    const container = document.getElementById('attendanceReports');
    if (!container) return;
    
    container.innerHTML = '<p class="loading">Loading attendance report...</p>';
    
    try {
        let url = `get_attendance_report.php?course_id=${courseId}&type=${type}`;
        if (type === 'daily') {
            const today = new Date().toISOString().split('T')[0];
            url += `&date=${today}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (!data.success) {
            container.innerHTML = `<p style="color: red;">Error: ${data.message}</p>`;
            return;
        }
        
        if (type === 'overall') {
            // Overall report
            const summary = data.summary;
            const percentage = summary.attendance_percentage || 0;
            const percentageColor = percentage >= 80 ? 'green' : percentage >= 60 ? 'orange' : 'red';
            
            container.innerHTML = `
                <div class="attendance-summary" style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h3>Overall Attendance Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: bold; color: ${percentageColor};">${percentage}%</div>
                            <div style="color: #666; font-size: 14px;">Attendance Rate</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: bold;">${summary.total_sessions || 0}</div>
                            <div style="color: #666; font-size: 14px;">Total Sessions</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: bold; color: green;">${summary.present_count || 0}</div>
                            <div style="color: #666; font-size: 14px;">Present</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: bold; color: orange;">${summary.late_count || 0}</div>
                            <div style="color: #666; font-size: 14px;">Late</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: bold; color: red;">${summary.absent_count || 0}</div>
                            <div style="color: #666; font-size: 14px;">Absent</div>
                        </div>
                    </div>
                </div>
                <div class="attendance-sessions">
                    <h3>Session Details</h3>
                    ${data.sessions && data.sessions.length > 0 ? data.sessions.map(session => {
                        const sessionDate = new Date(session.date);
                        const formattedDate = sessionDate.toLocaleDateString('en-US', { 
                            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' 
                        });
                        
                        let statusBadge = '';
                        if (session.status === 'present') {
                            statusBadge = '<span style="background: green; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Present</span>';
                        } else if (session.status === 'late') {
                            statusBadge = '<span style="background: orange; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Late</span>';
                        } else if (session.status === 'absent') {
                            statusBadge = '<span style="background: red; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Absent</span>';
                        } else {
                            statusBadge = '<span style="background: #ccc; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Not Marked</span>';
                        }
                        
                        return `
                            <div class="course-item" style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4>${formattedDate} - ${session.start_time} to ${session.end_time}</h4>
                                        ${session.topic ? `<p><strong>Topic:</strong> ${esc(session.topic)}</p>` : ''}
                                        ${session.check_in_time ? `<small><strong>Checked in at:</strong> ${session.check_in_time}</small>` : ''}
                                    </div>
                                    <div>${statusBadge}</div>
                                </div>
                            </div>
                        `;
                    }).join('') : '<p>No sessions found.</p>'}
                </div>
            `;
        } else {
            // Daily report
            container.innerHTML = `
                <h3>Daily Attendance - ${new Date(data.date).toLocaleDateString()}</h3>
                ${data.sessions && data.sessions.length > 0 ? data.sessions.map(session => {
                    let statusBadge = '';
                    if (session.status === 'present') {
                        statusBadge = '<span style="background: green; color: white; padding: 3px 8px; border-radius: 3px;">Present</span>';
                    } else if (session.status === 'late') {
                        statusBadge = '<span style="background: orange; color: white; padding: 3px 8px; border-radius: 3px;">Late</span>';
                    } else {
                        statusBadge = '<span style="background: #ccc; color: white; padding: 3px 8px; border-radius: 3px;">Not Marked</span>';
                    }
                    
                    return `
                        <div class="course-item">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4>${session.start_time} - ${session.end_time}</h4>
                                    ${session.topic ? `<p>${esc(session.topic)}</p>` : ''}
                                </div>
                                ${statusBadge}
                            </div>
                        </div>
                    `;
                }).join('') : '<p>No sessions scheduled for this date.</p>'}
            `;
        }
    } catch (error) {
        container.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    }
}

function esc(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getCurrentCourseId() {
    // Try to get course ID from URL or current context
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('course_id') || null;
}

