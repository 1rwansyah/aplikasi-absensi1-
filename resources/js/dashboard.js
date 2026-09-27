import Chart from 'chart.js/auto';

const DashboardModule = {
    chartTextColor() {
        return document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#4b5563';
    },

    chartMutedColor() {
        return document.documentElement.classList.contains('dark') ? '#94a3b8' : '#9ca3af';
    },

    baseChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: { top: 8, right: 8, bottom: 4, left: 4 },
            },
        };
    },

    init() {
        this.initWeeklyChart();
        this.initMonthlyChart();
        this.initStatusChart();
    },

    initWeeklyChart() {
        const ctx = document.getElementById('weeklyChart');
        if (!ctx) return;

        const labels = ctx.dataset.labels ? JSON.parse(ctx.dataset.labels) : [];
        const data = ctx.dataset.data ? JSON.parse(ctx.dataset.data) : [];
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.28)');
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Kehadiran',
                    data,
                    borderColor: 'rgb(37, 99, 235)',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    tension: 0.45,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: 'rgb(37, 99, 235)',
                    pointBorderWidth: 2,
                }],
            },
            options: {
                ...this.baseChartOptions(),
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.92)',
                        titleFont: { size: 12, weight: '600' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 10,
                        displayColors: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: this.chartMutedColor(),
                            font: { size: 11 },
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.18)',
                            drawBorder: false,
                        },
                        border: { display: false },
                    },
                    x: {
                        ticks: {
                            color: this.chartMutedColor(),
                            font: { size: 11 },
                        },
                        grid: { display: false },
                        border: { display: false },
                    },
                },
            },
        });
    },

    initMonthlyChart() {
        const ctx = document.getElementById('monthlyChart');
        if (!ctx) return;

        const labels = ctx.dataset.labels ? JSON.parse(ctx.dataset.labels) : [];
        const data = ctx.dataset.data ? JSON.parse(ctx.dataset.data) : [];

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Kehadiran',
                    data,
                    backgroundColor: 'rgba(16, 185, 129, 0.78)',
                    hoverBackgroundColor: 'rgba(5, 150, 105, 0.92)',
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 28,
                }],
            },
            options: {
                ...this.baseChartOptions(),
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.92)',
                        titleFont: { size: 12, weight: '600' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 10,
                        displayColors: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: this.chartMutedColor(),
                            font: { size: 11 },
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.18)',
                            drawBorder: false,
                        },
                        border: { display: false },
                    },
                    x: {
                        ticks: {
                            color: this.chartMutedColor(),
                            font: { size: 10 },
                            maxRotation: 45,
                            minRotation: 0,
                        },
                        grid: { display: false },
                        border: { display: false },
                    },
                },
            },
        });
    },

    initStatusChart() {
        const ctx = document.getElementById('statusChart');
        if (!ctx) return;

        const data = ctx.dataset.data ? JSON.parse(ctx.dataset.data) : [0, 0, 0, 0];
        const textColor = this.chartTextColor();
        const total = data.reduce((sum, value) => sum + Number(value || 0), 0);

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Terlambat', 'Tidak Hadir', 'Cuti'],
                datasets: [{
                    data,
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.9)',
                        'rgba(245, 158, 11, 0.9)',
                        'rgba(239, 68, 68, 0.9)',
                        'rgba(100, 116, 139, 0.9)',
                    ],
                    hoverOffset: 6,
                    borderWidth: 0,
                    spacing: 2,
                }],
            },
            options: {
                ...this.baseChartOptions(),
                cutout: '68%',
                radius: '88%',
                layout: {
                    padding: { top: 8, right: 12, bottom: 8, left: 12 },
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        align: 'center',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: 14,
                            color: textColor,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 11, weight: '500' },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.92)',
                        titleFont: { size: 12, weight: '600' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            label: (context) => {
                                const value = Number(context.raw || 0);
                                const percent = total > 0 ? Math.round((value / total) * 100) : 0;
                                return ` ${context.label}: ${value} (${percent}%)`;
                            },
                        },
                    },
                },
            },
        });
    },
};

export default DashboardModule;
