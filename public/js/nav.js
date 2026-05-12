function userNav() {
    return `
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center fw-bold text-uppercase" href="${baseUrl}/">
                    <img src="${baseUrl}images/logo_binan.png" class="navbar-logo me-2" alt="Logo">
                    Biñan City
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <div class="navbar-nav ms-auto">
                        <a class="nav-link" href="${baseUrl}user/dashboard">
                            <i class="fas fa-home"></i> Home
                        </a>
                        <a class="nav-link" href="${baseUrl}user/vouchers">
                            <i class="fas fa-users"></i> Students
                        </a>
                        <a class="nav-link" href="${baseUrl}/signatories">
                            <i class="fas fa-signature"></i> Signatories
                        </a>
                        <a class="nav-link" href="${baseUrl}/audit-logs">
                            <i class="fas fa-clipboard-list"></i> Audit Logs
                        </a>
                        <a class="nav-link" href="${baseUrl}logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    `;
}

function adminNav() {
    return `
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center fw-bold text-uppercase" href="${baseUrl}/">
                    <img src="${baseUrl}images/logo_binan.png" class="navbar-logo me-2" alt="Logo">
                    Biñan City
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <div class="navbar-nav ms-auto">
                        <a class="nav-link" href="${baseUrl}admin/dashboard">
                            <i class="fas fa-home"></i> Home
                        </a>
                        <a class="nav-link" href="${baseUrl}admin/">
                            <i class="fas fa-users-cog"></i> Users
                        </a>
                        <a class="nav-link" href="${baseUrl}user/vouchers">
                            <i class="fas fa-users"></i> Students
                        </a>
                        <a class="nav-link" href="${baseUrl}/signatories">
                            <i class="fas fa-signature"></i> Signatories
                        </a>
                        <a class="nav-link" href="${baseUrl}/archive">
                            <i class="fas fa-archive"></i> Student Archive
                        </a>
                        <a class="nav-link" href="${baseUrl}admin/archived_users">
                            <i class="fas fa-user-slash"></i> User Archive
                        </a>
                        <a class="nav-link" href="${baseUrl}/audit-logs">
                            <i class="fas fa-clipboard-list"></i> Audit Logs
                        </a>
                        <a class="nav-link" href="${baseUrl}logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    `;
}

function authNav() {
    return `
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 fixed-top shadow">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center fw-bold text-uppercase" href="#">
                    <img src="${baseUrl}images/logo_binan.png" class="navbar-logo me-2" alt="Logo">
                    Biñan City
                </a>
            </div>
        </nav>
    `;
}

document.addEventListener('DOMContentLoaded', function() {
    const navContainer = document.getElementById('nav-container');
    let navHtml = '';
    if (navType === 'auth') {
        navHtml = authNav();
    } else if (navType === 'admin') {
        navHtml = adminNav();
    } else if (navType === 'user') {
        navHtml = userNav();
    }
    if (navContainer) {
        navContainer.innerHTML = navHtml;
    }
});
