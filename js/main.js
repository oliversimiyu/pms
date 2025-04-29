/**
 * BTTI Resource Management System - Main JavaScript
 * Handles Bootstrap component initialization and other interactive elements
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize all popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialize all modals
    var modalTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="modal"]'));
    modalTriggerList.forEach(function(modalTriggerEl) {
        modalTriggerEl.addEventListener('click', function() {
            var targetModal = document.querySelector(this.getAttribute('data-bs-target'));
            if (targetModal) {
                var modal = new bootstrap.Modal(targetModal);
                modal.show();
            }
        });
    });
    
    // Fix for select dropdowns in modals
    var selectElements = document.querySelectorAll('select.form-select');
    selectElements.forEach(function(select) {
        select.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});
