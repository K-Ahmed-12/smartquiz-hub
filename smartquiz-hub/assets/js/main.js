/**
 * SmartQuiz Hub - Main JavaScript File
 */

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeAnimations();
    initializeFormValidation();
    initializeTooltips();
    initializeAlerts();
});

// Theme Management
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    // Set initial theme
    applyTheme(currentTheme);
    
    // Theme toggle event listener
    if (themeToggle) {
        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }
}

function applyTheme(theme) {
    // Apply to document element
    document.documentElement.setAttribute('data-theme', theme);
    
    // Apply to body as well for better coverage
    document.body.setAttribute('data-theme', theme);
    
    // Update icon
    updateThemeIcon(theme);
    
    // Add transition class for smooth switching
    document.body.classList.add('theme-transition');
    setTimeout(() => {
        document.body.classList.remove('theme-transition');
    }, 300);
}

function updateThemeIcon(theme) {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const icon = themeToggle.querySelector('i');
        if (icon) {
            if (theme === 'dark') {
                icon.className = 'fas fa-sun';
                themeToggle.setAttribute('title', 'Switch to Light Mode');
            } else {
                icon.className = 'fas fa-moon';
                themeToggle.setAttribute('title', 'Switch to Dark Mode');
            }
        }
    }
}

// Animations
function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animateElements = document.querySelectorAll('.feature-card, .quiz-card, .category-card');
    animateElements.forEach(el => observer.observe(el));
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Auto-hide alerts
function initializeAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.classList.remove('show');
                setTimeout(() => {
                    if (alert && alert.parentNode) {
                        alert.remove();
                    }
                }, 150);
            }
        }, 5000);
    });
}

// Utility Functions
function showLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="loading"></span> Loading...';
    button.disabled = true;
    button.setAttribute('data-original-text', originalText);
}

function hideLoading(button) {
    const originalText = button.getAttribute('data-original-text');
    if (originalText) {
        button.innerHTML = originalText;
        button.disabled = false;
        button.removeAttribute('data-original-text');
    }
}

function showAlert(message, type = 'info', container = null) {
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHTML);
    } else {
        const alertContainer = document.querySelector('.alert-container') || document.body;
        alertContainer.insertAdjacentHTML('afterbegin', alertHTML);
    }
}

function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    } else {
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// AJAX Helper Functions
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Request failed:', error);
            throw error;
        });
}

// Quiz-specific functions
function startQuiz(quizId) {
    if (confirm('Are you ready to start this quiz? The timer will begin immediately.')) {
        window.location.href = `take-quiz.php?id=${quizId}`;
    }
}

function submitQuiz(formId) {
    const form = document.getElementById(formId);
    if (form && confirm('Are you sure you want to submit your quiz? You cannot change your answers after submission.')) {
        showLoading(document.querySelector('#submitBtn'));
        form.submit();
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    
    if (searchInput && searchResults) {
        const debouncedSearch = debounce(performSearch, 300);
        
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                debouncedSearch(query);
            } else {
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
            }
        });
        
        // Hide search results when clicking outside
        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
}

function performSearch(query) {
    const searchResults = document.getElementById('searchResults');
    
    makeRequest(`api/search.php?q=${encodeURIComponent(query)}`)
        .then(data => {
            displaySearchResults(data.results);
        })
        .catch(error => {
            console.error('Search failed:', error);
            searchResults.innerHTML = '<div class="p-3 text-muted">Search failed. Please try again.</div>';
            searchResults.style.display = 'block';
        });
}

function displaySearchResults(results) {
    const searchResults = document.getElementById('searchResults');
    
    if (results.length === 0) {
        searchResults.innerHTML = '<div class="p-3 text-muted">No results found.</div>';
    } else {
        const resultsHTML = results.map(result => `
            <a href="quiz.php?id=${result.id}" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${result.title}</h6>
                    <small class="text-muted">${result.category}</small>
                </div>
                <p class="mb-1">${result.description}</p>
                <small class="text-muted">${result.difficulty} • ${result.time_limit} min</small>
            </a>
        `).join('');
        
        searchResults.innerHTML = resultsHTML;
    }
    
    searchResults.style.display = 'block';
}

// Filter functionality
function initializeFilters() {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('select, input[type="checkbox"]');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                applyFilters();
            });
        });
    }
}

function applyFilters() {
    const filterForm = document.getElementById('filterForm');
    const formData = new FormData(filterForm);
    const params = new URLSearchParams(formData);
    
    // Update URL without page reload
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState({}, '', newUrl);
    
    // Reload quiz list
    loadQuizzes(params.toString());
}

function loadQuizzes(params = '') {
    const quizContainer = document.getElementById('quizContainer');
    const loadingIndicator = document.getElementById('loadingIndicator');
    
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
    
    makeRequest(`api/get-quizzes.php?${params}`)
        .then(data => {
            displayQuizzes(data.quizzes);
            updatePagination(data.pagination);
        })
        .catch(error => {
            console.error('Failed to load quizzes:', error);
            showAlert('Failed to load quizzes. Please try again.', 'danger');
        })
        .finally(() => {
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
        });
}

function displayQuizzes(quizzes) {
    const quizContainer = document.getElementById('quizContainer');
    
    if (quizzes.length === 0) {
        quizContainer.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No quizzes found matching your criteria.</p></div>';
        return;
    }
    
    const quizzesHTML = quizzes.map(quiz => `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="quiz-card">
                <div class="quiz-card-header">
                    <span class="badge bg-primary">${quiz.category_name}</span>
                    <span class="badge bg-secondary">${quiz.difficulty}</span>
                </div>
                <div class="quiz-card-body">
                    <h5 class="quiz-title">${quiz.title}</h5>
                    <p class="quiz-description">${quiz.description.substring(0, 100)}...</p>
                    <div class="quiz-meta">
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>${quiz.time_limit} min</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-star"></i>
                            <span>${quiz.total_marks} marks</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span>${quiz.attempt_count} attempts</span>
                        </div>
                    </div>
                </div>
                <div class="quiz-card-footer">
                    <button onclick="startQuiz(${quiz.id})" class="btn btn-primary w-100">
                        <i class="fas fa-play me-2"></i>Start Quiz
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    
    quizContainer.innerHTML = quizzesHTML;
}

function updatePagination(pagination) {
    const paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer || !pagination) return;
    
    let paginationHTML = '';
    
    if (pagination.total_pages > 1) {
        paginationHTML = '<nav><ul class="pagination justify-content-center">';
        
        // Previous button
        if (pagination.current_page > 1) {
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${pagination.current_page - 1})">Previous</a></li>`;
        }
        
        // Page numbers
        for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
            const activeClass = i === pagination.current_page ? 'active' : '';
            paginationHTML += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="loadPage(${i})">${i}</a></li>`;
        }
        
        // Next button
        if (pagination.current_page < pagination.total_pages) {
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${pagination.current_page + 1})">Next</a></li>`;
        }
        
        paginationHTML += '</ul></nav>';
    }
    
    paginationContainer.innerHTML = paginationHTML;
}

function loadPage(page) {
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.set('page', page);
    
    const newUrl = `${window.location.pathname}?${currentParams.toString()}`;
    window.history.pushState({}, '', newUrl);
    
    loadQuizzes(currentParams.toString());
}

// Initialize page-specific functionality
function initializePage() {
    const currentPage = document.body.getAttribute('data-page');
    
    switch (currentPage) {
        case 'quizzes':
            initializeSearch();
            initializeFilters();
            break;
        case 'quiz-taking':
            initializeQuizTimer();
            initializeAutoSave();
            break;
        case 'dashboard':
            initializeCharts();
            break;
    }
}

// Call initialization when DOM is ready
document.addEventListener('DOMContentLoaded', initializePage);
