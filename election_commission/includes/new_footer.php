<?php
require_once __DIR__ . '/../../version.php';
?>
<footer class="footer">
    <div class="footer-content">
        <div class="copyright">
            <span>&copy; <?php echo SYSTEM_COPYRIGHT_YEAR; ?> <?php echo SYSTEM_FULL_NAME; ?></span>
            <span class="separator">|</span>
            <span>Developed with <span class="heart">&hearts;</span> by 
                <a href="mailto:giftlameck2024@gmail.com" class="developer-link"><?php echo SYSTEM_DEVELOPER; ?></a>
            </span>
        </div>
        <div class="version-info">
            <span>Version <?php echo SYSTEM_VERSION; ?></span>
        </div>
    </div>
</footer>

<style>
.footer {
    background-color: #2c3e50;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem 0;
    font-size: 0.9rem;
    width: 100%;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    z-index: 999;
    margin-top: 2rem;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.copyright {
    color: #ecf0f1;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.separator {
    color: rgba(255, 255, 255, 0.3);
    margin: 0 0.5rem;
}

.developer-link {
    color: #3498db;
    text-decoration: none;
    transition: color 0.3s ease;
    font-weight: 500;
}

.developer-link:hover {
    color: #2980b9;
    text-decoration: underline;
}

.version-info {
    color: #ecf0f1;
    font-size: 0.85rem;
    padding: 0.25rem 0.75rem;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
}

.heart {
    color: #e74c3c;
    margin: 0 0.25rem;
    display: inline-block;
    animation: pulse 1.5s ease infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }

    .copyright {
        justify-content: center;
    }

    .separator {
        display: none;
    }
}

/* Adjust footer for sidebar */
@media (min-width: 769px) {
    .footer {
        width: 100%;
    }
}
</style>

<!-- Local Bootstrap JS and dependencies -->
<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/chart.min.js"></script>

<!-- Custom JS -->
<script>
    // Toggle sidebar on mobile
    document.querySelector('.navbar-toggler').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            const sidebar = document.querySelector('.sidebar');
            const toggler = document.querySelector('.navbar-toggler');
            if (!sidebar.contains(e.target) && !toggler.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
</script>
</body>
</html> 