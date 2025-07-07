// Authentication utility functions
class AuthManager {
    constructor() {
        this.currentUser = null;
        this.sessionToken = null;
    }

    async checkAuth() {
        try {
            const response = await fetch('api/auth.php?action=validate');
            const result = await response.json();

            if (result.success) {
                this.currentUser = result.user;
                this.updateUserDisplay();
                return true;
            } else {
                this.redirectToLogin();
                return false;
            }
        } catch (error) {
            console.error('Auth check failed:', error);
            this.redirectToLogin();
            return false;
        }
    }

    async logout() {
        try {
            const response = await fetch('api/auth.php?action=logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({})
            });

            const result = await response.json();

            if (result.success) {
                this.currentUser = null;
                this.sessionToken = null;
                this.redirectToLogin();
            }
        } catch (error) {
            console.error('Logout failed:', error);
            this.redirectToLogin();
        }
    }

    updateUserDisplay() {
        if (this.currentUser) {
            const userInfo = document.querySelector('.user-info');
            if (userInfo) {
                userInfo.innerHTML = `
                    <div class="user-dropdown">
                        <span class="user-name">
                            <i class="fas fa-user"></i> ${this.currentUser.full_name || this.currentUser.username}
                        </span>
                        <div class="user-menu">
                            <a href="#" onclick="showProfile()">
                                <i class="fas fa-user-cog"></i> Profile
                            </a>
                            <a href="#" onclick="showSettings()">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <a href="#" onclick="authManager.logout()">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                `;
            }
        }
    }

    redirectToLogin() {
        window.location.href = 'login.html';
    }

    async getProfile() {
        try {
            const response = await fetch('api/auth.php?action=profile');
            const result = await response.json();

            if (result.success) {
                return result.user;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Failed to get profile:', error);
            throw error;
        }
    }

    async updateProfile(profileData) {
        try {
            const response = await fetch('api/auth.php?action=profile', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(profileData)
            });

            const result = await response.json();

            if (result.success) {
                this.currentUser = { ...this.currentUser, ...profileData };
                this.updateUserDisplay();
                return true;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Failed to update profile:', error);
            throw error;
        }
    }

    async changePassword(currentPassword, newPassword) {
        try {
            const response = await fetch('api/auth.php?action=password', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            });

            const result = await response.json();

            if (result.success) {
                return true;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Failed to change password:', error);
            throw error;
        }
    }
}

// Initialize auth manager
const authManager = new AuthManager();

// Profile management functions
async function showProfile() {
    try {
        const profile = await authManager.getProfile();

        const profileModal = document.createElement('div');
        profileModal.className = 'modal';
        profileModal.id = 'profile-modal';
        profileModal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Profile Settings</h3>
                    <span class="close" onclick="closeProfileModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="profile-tabs">
                        <button class="profile-tab active" onclick="showProfileTab('info')">Profile Info</button>
                        <button class="profile-tab" onclick="showProfileTab('password')">Change Password</button>
                    </div>
                    
                    <div id="profile-info" class="profile-content active">
                        <form id="profile-form">
                            <div class="form-group">
                                <label for="profile-username">Username</label>
                                <input type="text" id="profile-username" value="${profile.username}" disabled>
                            </div>
                            <div class="form-group">
                                <label for="profile-email">Email</label>
                                <input type="email" id="profile-email" name="email" value="${profile.email}" required>
                            </div>
                            <div class="form-group">
                                <label for="profile-fullname">Full Name</label>
                                <input type="text" id="profile-fullname" name="full_name" value="${profile.full_name || ''}" required>
                            </div>
                            <div class="form-group">
                                <label>Member Since</label>
                                <input type="text" value="${new Date(profile.created_at).toLocaleDateString()}" disabled>
                            </div>
                            <div class="form-group">
                                <label>Last Login</label>
                                <input type="text" value="${profile.last_login ? new Date(profile.last_login).toLocaleString() : 'Never'}" disabled>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                    
                    <div id="profile-password" class="profile-content">
                        <form id="password-form">
                            <div class="form-group">
                                <label for="current-password">Current Password</label>
                                <input type="password" id="current-password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new-password">New Password</label>
                                <input type="password" id="new-password" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm-password">Confirm New Password</label>
                                <input type="password" id="confirm-password" name="confirm_password" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(profileModal);
        profileModal.style.display = 'block';

        // Add event listeners
        document.getElementById('profile-form').addEventListener('submit', handleProfileUpdate);
        document.getElementById('password-form').addEventListener('submit', handlePasswordChange);

    } catch (error) {
        showMessage('Failed to load profile: ' + error.message, 'error');
    }
}

function showProfileTab(tabName) {
    // Remove active class from all tabs and content
    document.querySelectorAll('.profile-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.profile-content').forEach(content => content.classList.remove('active'));

    // Add active class to selected tab and content
    document.querySelector(`[onclick="showProfileTab('${tabName}')"]`).classList.add('active');
    document.getElementById(`profile-${tabName}`).classList.add('active');
}

async function handleProfileUpdate(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const profileData = {
        email: formData.get('email'),
        full_name: formData.get('full_name')
    };

    try {
        await authManager.updateProfile(profileData);
        showMessage('Profile updated successfully!', 'success');
        closeProfileModal();
    } catch (error) {
        showMessage('Failed to update profile: ' + error.message, 'error');
    }
}

async function handlePasswordChange(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const currentPassword = formData.get('current_password');
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');

    if (newPassword !== confirmPassword) {
        showMessage('New passwords do not match', 'error');
        return;
    }

    if (newPassword.length < 6) {
        showMessage('New password must be at least 6 characters long', 'error');
        return;
    }

    try {
        await authManager.changePassword(currentPassword, newPassword);
        showMessage('Password changed successfully!', 'success');
        closeProfileModal();
    } catch (error) {
        showMessage('Failed to change password: ' + error.message, 'error');
    }
}

function closeProfileModal() {
    const modal = document.getElementById('profile-modal');
    if (modal) {
        modal.remove();
    }
}

function showSettings() {
    showMessage('Settings panel coming soon!', 'success');
}

// Initialize authentication on page load
document.addEventListener('DOMContentLoaded', function () {
    authManager.checkAuth();
});

// Close profile modal when clicking outside
window.addEventListener('click', function (event) {
    const modal = document.getElementById('profile-modal');
    if (event.target === modal) {
        closeProfileModal();
    }
});
