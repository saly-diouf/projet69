// MarketStudy Pro - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'all 0.3s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Likert scale interaction
    document.querySelectorAll('.likert-option').forEach(option => {
        option.addEventListener('click', function() {
            const group = this.closest('.likert-scale');
            group.querySelectorAll('.likert-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            const input = this.querySelector('input');
            if (input) input.checked = true;
        });
    });

    // Confirm delete
    document.querySelectorAll('.confirm-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });

    // Dynamic options for questionnaire builder
    const addOptionBtn = document.getElementById('add-option');
    if (addOptionBtn) {
        addOptionBtn.addEventListener('click', function() {
            const container = document.getElementById('options-container');
            const count = container.children.length;
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 mb-2';
            div.innerHTML = `
                <input type="text" name="options[]" class="form-control" placeholder="Option ${count + 1}" required>
                <button type="button" class="btn btn-danger btn-sm remove-option"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(div);
            div.querySelector('.remove-option').addEventListener('click', () => div.remove());
        });
    }

    // Remove option buttons
    document.querySelectorAll('.remove-option').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.flex').remove();
        });
    });

    // Toggle question type fields
    const typeSelect = document.getElementById('question-type');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            const type = this.value;
            const optionsDiv = document.getElementById('options-fields');
            const scaleDiv = document.getElementById('scale-fields');
            const needsOptions = ['fermee_une', 'fermee_multiple', 'likert', 'classement'].includes(type);
            const needsScale = ['echelle'].includes(type);

            if (optionsDiv) optionsDiv.style.display = needsOptions ? 'block' : 'none';
            if (scaleDiv) scaleDiv.style.display = needsScale ? 'block' : 'none';
        });
        typeSelect.dispatchEvent(new Event('change'));
    }

    // Survey form: skip logic
    document.querySelectorAll('[data-skip-logic]').forEach(input => {
        input.addEventListener('change', function() {
            const skipData = JSON.parse(this.dataset.skipLogic);
            const targetValue = this.value;
            if (skipData[targetValue]) {
                const targetQuestion = document.getElementById('question-' + skipData[targetValue]);
                if (targetQuestion) {
                    // Hide all questions after current, show target
                    document.querySelectorAll('.question-card').forEach(q => q.style.display = 'block');
                    // Could implement more complex skip logic here
                }
            }
        });
    });

    // Copy to clipboard
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const target = document.getElementById(this.dataset.target);
            if (target) {
                target.select();
                document.execCommand('copy');
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copié !';
                setTimeout(() => { this.innerHTML = originalText; }, 2000);
            }
        });
    });
});

// Chart helpers
function createBarChart(canvasId, labels, data, label = 'Effectifs', colors = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const defaultColors = [
        '#4f46e5', '#ec4899', '#10b981', '#f59e0b',
        '#0ea5e9', '#8b5cf6', '#ef4444', '#14b8a6',
        '#f97316', '#6366f1', '#db2777', '#059669'
    ];

    const bgColors = colors || labels.map((_, i) => defaultColors[i % defaultColors.length]);

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: data,
                backgroundColor: bgColors,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 12,
                    borderRadius: 8,
                    titleFont: { family: 'Inter', size: 13 },
                    bodyFont: { family: 'Inter', size: 13 },
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f4f6' },
                    ticks: { font: { family: 'Inter', size: 12 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 12 } }
                }
            }
        }
    });
}

function createPieChart(canvasId, labels, data, colors = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const defaultColors = [
        '#4f46e5', '#ec4899', '#10b981', '#f59e0b',
        '#0ea5e9', '#8b5cf6', '#ef4444', '#14b8a6',
        '#f97316', '#6366f1', '#db2777', '#059669'
    ];

    const bgColors = colors || labels.map((_, i) => defaultColors[i % defaultColors.length]);

    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: bgColors,
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { font: { family: 'Inter', size: 13 }, padding: 12 }
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 12,
                    borderRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + context.parsed + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
}

function createLineChart(canvasId, labels, data, label = 'Valeurs') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: data,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79,70,229,0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointBackgroundColor: '#4f46e5',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });
}

function createScatterChart(canvasId, data, label = 'Individus') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const colors = ['#4f46e5', '#ec4899', '#10b981', '#f59e0b', '#0ea5e9', '#8b5cf6', '#ef4444', '#14b8a6'];
    const datasets = [];

    if (data.groups) {
        data.groups.forEach((group, i) => {
            datasets.push({
                label: 'Cluster ' + (i + 1),
                data: group,
                backgroundColor: colors[i % colors.length],
            });
        });
    } else {
        datasets.push({
            label: label,
            data: data.points,
            backgroundColor: '#4f46e5',
        });
    }

    return new Chart(ctx, {
        type: 'scatter',
        data: { datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { family: 'Inter', size: 13 } }
                },
            },
            scales: {
                x: {
                    title: { display: true, text: data.xLabel || 'Composante 1', font: { family: 'Inter' } },
                    grid: { color: '#f3f4f6' }
                },
                y: {
                    title: { display: true, text: data.yLabel || 'Composante 2', font: { family: 'Inter' } },
                    grid: { color: '#f3f4f6' }
                }
            }
        }
    });
}

function createRadarChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { family: 'Inter', size: 13 } }
                },
            },
            scales: {
                r: {
                    beginAtZero: true,
                    grid: { color: '#e5e7eb' },
                    angleLines: { color: '#e5e7eb' },
                    pointLabels: { font: { family: 'Inter', size: 12 } }
                }
            }
        }
    });
}
