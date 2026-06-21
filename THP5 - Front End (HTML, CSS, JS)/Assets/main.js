/**
 * SI Praktek Dokter - Main JavaScript File
 * Mengelola modal, form, dan interaksi user
 */

document.addEventListener("DOMContentLoaded", function() {
    console.log("✓ Aplikasi dimulai");
    
    // Check user session untuk dashboard
    checkUserSession();
    
    // Initialize modal functionality
    initializeModal();
    
    // Initialize form handlers
    initializeFormHandlers();
    
    // Initialize button handlers
    initializeButtonHandlers();
    
    // Initialize table interactions
    initializeTableInteractions();
});

/**
 * Check User Session - Redirect ke login jika tidak ada session
 */
function checkUserSession() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const userLogin = localStorage.getItem("userLogin");
    
    // Halaman yang tidak perlu login check
    const publicPages = ['login.html', 'index.html', ''];
    
    if (!publicPages.includes(currentPage) && !userLogin) {
        console.log("⚠ Tidak ada session, redirect ke login");
        window.location.href = "login.html";
    }
    
    // Update user display name
    if (userLogin && document.getElementById("userDisplay")) {

        document.getElementById("userDisplay").textContent = userLogin;
    }
}

/**
 * Initialize Modal Functionality
 */
function initializeModal() {
    const modal = document.getElementById("formModal");
    const btnAddElements = document.querySelectorAll("#btnAdd");
    const spanClose = document.getElementsByClassName("close-modal")[0];

    // Multiple "Add" buttons
    btnAddElements.forEach(btnAdd => {
        btnAdd.addEventListener("click", function(e) {
            e.preventDefault();
            if (modal) {
                modal.style.display = "flex";
                // Reset form
                const form = modal.querySelector("form");
                if (form) form.reset();
                console.log("✓ Modal dibuka");
            }
        });
    });

    // Close button
    if (spanClose && modal) {
        spanClose.addEventListener("click", function() {
            closeModal();
        });
    }

    // Close when clicking outside
    if (modal) {
        window.addEventListener("click", function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    // Close with ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape" && modal && modal.style.display === "flex") {
            closeModal();
        }
    });
}

/**
 * Close Modal Function
 */
function closeModal() {
    const modal = document.getElementById("formModal");
    if (modal) {
        modal.style.display = "none";
        console.log("✓ Modal ditutup");
    }
}

/**
 * Initialize Form Handlers
 */
function initializeFormHandlers() {
    const forms = document.querySelectorAll("#formModal form");
    
    forms.forEach(form => {
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            
            // Collect form data
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            console.log("✓ Form data:", data);
            
            // Show success message
            showNotification("Data berhasil disimpan!", "success");
            
            // Close modal
            closeModal();
            
            // Reset form
            this.reset();
            
            // Simulate data save
            setTimeout(() => {
                // Tambah sample row ke table jika ada
                addRowToTable(data);
            }, 500);
        });
    });
}

/**
 * Add Row to Table (Simulasi)
 */
function addRowToTable(data) {
    const tables = document.querySelectorAll("table tbody");
    if (tables.length > 0) {
        const table = tables[0];
        const row = table.insertRow(-1);
        
        // Sesuaikan dengan jumlah kolom
        const cols = table.closest("table").querySelectorAll("th").length;
        for (let i = 0; i < cols - 1; i++) {
            const cell = row.insertCell(i);
            cell.textContent = Object.values(data)[i] || "-";
        }
        
        // Action cell
        const actionCell = row.insertCell(-1);
        actionCell.innerHTML = '<button class="btn btn-small">Edit</button> <button class="btn btn-small btn-danger">Hapus</button>';
        
        console.log("✓ Row ditambah ke table");
        showNotification("Data ditambahkan ke tabel", "success");
    }
}

/**
 * Initialize Button Handlers
 */
function initializeButtonHandlers() {
    // Handle Edit buttons
    handleButtonType("Edit", function(e) {
        const row = e.target.closest("tr");
        showNotification("Edit mode - Data row: " + (row.rowIndex || ""), "info");
        console.log("✓ Edit button clicked");
    });
    
    // Handle Delete buttons
    handleButtonType("Hapus", function(e) {
        if (confirm("Apakah Anda yakin ingin menghapus data ini?")) {
            const row = e.target.closest("tr");
            if (row) {
                row.remove();
                showNotification("Data berhasil dihapus!", "success");
                console.log("✓ Row deleted");
            }
        }
    });
    
    // Handle Periksa buttons
    handleButtonType("Periksa", function(e) {
        const row = e.target.closest("tr");
        const patientName = row.cells[2]?.textContent || "Pasien";
        showNotification("Mulai pemeriksaan untuk " + patientName, "info");
        console.log("✓ Periksa clicked");
    });
    
    // Handle Bayar buttons
    handleButtonType("Bayar", function(e) {
        const row = e.target.closest("tr");
        const invoiceNo = row.cells[0]?.textContent || "INV-001";
        showNotification("Proses pembayaran untuk " + invoiceNo, "info");
        console.log("✓ Bayar clicked");
    });
    
    // Handle Proses Obat buttons
    handleButtonType("Proses Obat", function(e) {
        const modal = document.getElementById("formModal");
        if (modal) {
            modal.style.display = "flex";
            showNotification("Form resep dibuka", "info");
            console.log("✓ Proses Obat clicked");
        }
    });
    
    // Handle Isi RM buttons
    handleButtonType("Isi RM", function(e) {
        const modal = document.getElementById("formModal");
        if (modal) {
            modal.style.display = "flex";
            showNotification("Form rekam medis dibuka", "info");
            console.log("✓ Isi RM clicked");
        }
    });
    
    // Handle Riwayat buttons
    handleButtonType("Riwayat", function(e) {
        showNotification("Menampilkan riwayat pasien", "info");
        console.log("✓ Riwayat clicked");
    });
    
    // Handle Reset Password buttons
    handleButtonType("Reset Password", function(e) {
        showNotification("Password berhasil di-reset ke: klinik123", "success");
        console.log("✓ Reset Password clicked");
    });
    
    // Handle Nonaktifkan buttons
    handleButtonType("Nonaktifkan", function(e) {
        if (confirm("Apakah Anda yakin ingin menonaktifkan user ini?")) {
            const row = e.target.closest("tr");
            showNotification("User berhasil dinonaktifkan", "success");
            console.log("✓ User deactivated");
        }
    });
    
    // Handle Logout
    setupLogoutButton();
}

/**
 * Handle specific button types
 */
function handleButtonType(buttonText, callback) {
    const buttons = document.querySelectorAll("button");
    buttons.forEach(btn => {
        if (btn.textContent.includes(buttonText) && 
            !btn.textContent.includes("Tambah") && 
            !btn.id === "btnAdd" &&
            btn.type !== "submit" &&
            btn.getAttribute("onclick") !== "logout()") {
            
            btn.addEventListener("click", callback);
        }
    });
}

/**
 * Setup Logout Button
 */
function setupLogoutButton() {
    const logoutBtns = document.querySelectorAll("[onclick*='logout']");
    logoutBtns.forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            logout();
        });
    });
}

/**
 * Logout Function
 */
function logout() {
    if (confirm("Apakah Anda yakin ingin logout?")) {
        localStorage.removeItem("userLogin");
        showNotification("Logout berhasil", "success");
        setTimeout(() => {
            window.location.href = "login.html";
        }, 1000);
    }
}

/**
 * Initialize Table Interactions
 */
function initializeTableInteractions() {
    const tables = document.querySelectorAll("table");
    tables.forEach(table => {
        table.addEventListener("click", function(e) {
            if (e.target.tagName === "BUTTON") {
                const row = e.target.closest("tr");
                console.log("✓ Table button clicked at row:", row.rowIndex);
            }
        });
    });
}

/**
 * Show Notification Toast
 */
function showNotification(message, type = "info") {
    // Remove existing notification
    const existing = document.querySelector(".notification");
    if (existing) existing.remove();
    
    // Create notification element
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Determine color based on type
    let bgColor = "#3498db"; // info
    if (type === "success") bgColor = "#27ae60";
    if (type === "error") bgColor = "#dc3545";
    if (type === "warning") bgColor = "#f39c12";
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background-color: ${bgColor};
        color: white;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease-in-out;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove
    setTimeout(() => {
        notification.style.animation = "slideOut 0.3s ease-in-out";
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

/**
 * Format Currency
 */
function formatCurrency(value) {
    return "Rp " + parseInt(value).toLocaleString('id-ID');
}

/**
 * Validate Email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate Phone Number (Indonesia)
 */
function validatePhone(phone) {
    const re = /^(\+62|62|0)[0-9]{9,12}$/;
    return re.test(phone);
}

// Add CSS animations for notification
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .notification {
        font-family: 'Inter', sans-serif;
    }
`;
document.head.appendChild(style);

console.log("✓ Semua button event listener terdaftar");