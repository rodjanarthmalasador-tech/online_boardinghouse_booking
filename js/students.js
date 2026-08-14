const studentTable = document.getElementById('studentTable');
const studentForm = document.getElementById('studentForm');
const studentModal = document.getElementById('studentModal');
const searchInput = document.getElementById('searchInput');
const refreshBtn = document.getElementById('refreshBtn');
const studentIdInput = document.getElementById('studentId');
const studentNameInput = document.getElementById('studentName');
const studentCourseInput = document.getElementById('studentCourse');
const modalTitle = document.getElementById('studentModalLabel');

async function loadStudents() {
    const search = searchInput ? searchInput.value.trim() : '';
    const response = await fetch(`api/get_students.php?search=${encodeURIComponent(search)}`);
    const students = await response.json();

    if (!Array.isArray(students)) {
        studentTable.innerHTML = `<tr><td colspan="4" class="text-danger">${students.error || 'Unable to load students.'}</td></tr>`;
        return;
    }

    studentTable.innerHTML = '';

    if (students.length === 0) {
        studentTable.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No students found.</td></tr>';
        return;
    }

    students.forEach(student => {
        studentTable.innerHTML += `
            <tr>
                <td>${student.id}</td>
                <td>${student.name}</td>
                <td>${student.course}</td>
                <td>
                    <button class="btn btn-warning btn-sm me-2" onclick="editStudent(${student.id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(${student.id})">Delete</button>
                </td>
            </tr>
        `;
    });
}

function openModal() {
    const modal = bootstrap.Modal.getOrCreateInstance(studentModal);
    modal.show();
}

async function editStudent(id) {
    const response = await fetch(`api/get_student.php?id=${id}`);
    const student = await response.json();

    if (student.error) {
        alert(student.error);
        return;
    }

    studentIdInput.value = student.id;
    studentNameInput.value = student.name;
    studentCourseInput.value = student.course;
    modalTitle.textContent = 'Edit Student';
    openModal();
}

async function deleteStudent(id) {
    const confirmed = confirm('Are you sure you want to delete this student?');
    if (!confirmed) return;

    const response = await fetch('api/delete_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });

    const result = await response.json();
    if (result.error) {
        alert(result.error);
        return;
    }

    alert(result.message || 'Student deleted.');
    loadStudents();
}

function resetForm() {
    studentForm.reset();
    studentIdInput.value = '';
    modalTitle.textContent = 'Student Form';
}

studentForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    const id = studentIdInput.value;
    const name = studentNameInput.value.trim();
    const course = studentCourseInput.value.trim();

    if (!name || !course) {
        alert('Please enter both name and course.');
        return;
    }

    const url = id ? 'api/update_student.php' : 'api/add_student.php';
    const payload = { id: id ? Number(id) : null, name, course };

    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.error) {
        alert(result.error);
        return;
    }

    const modal = bootstrap.Modal.getInstance(studentModal);
    if (modal) modal.hide();

    resetForm();
    alert(result.message || 'Student saved successfully.');
    loadStudents();
});

searchInput.addEventListener('input', loadStudents);
refreshBtn.addEventListener('click', loadStudents);

document.addEventListener('DOMContentLoaded', loadStudents);

window.editStudent = editStudent;
window.deleteStudent = deleteStudent;
