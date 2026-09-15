(() => {
    'use strict';

    const root = document.getElementById('portal-stats-data');
    if (!root || typeof Chart === 'undefined') {
        return;
    }

    let data;
    try {
        data = JSON.parse(root.textContent || '{}');
    } catch (e) {
        return;
    }

    const portal = document.querySelector('.portal') || document.body;
    const styles = getComputedStyle(portal);
    const text = styles.getPropertyValue('--portal-text-secondary').trim() || '#a3a3a3';
    const grid = styles.getPropertyValue('--portal-border').trim() || 'rgba(255,255,255,0.09)';
    const info = styles.getPropertyValue('--portal-info').trim() || '#60a5fa';
    const success = styles.getPropertyValue('--portal-success').trim() || '#34d399';
    const warning = styles.getPropertyValue('--portal-warning').trim() || '#fbbf24';
    const muted = styles.getPropertyValue('--portal-text-muted').trim() || '#7d7d7d';
    const error = styles.getPropertyValue('--portal-error').trim() || '#f87171';

    const hexToRgba = (color, alpha) => {
        const value = color.trim();
        if (value.startsWith('rgb')) {
            return value.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
        }
        const hex = value.replace('#', '');
        if (hex.length !== 6) {
            return `rgba(96, 165, 250, ${alpha})`;
        }
        const n = parseInt(hex, 16);
        return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
    };

    Chart.defaults.color = text;
    Chart.defaults.borderColor = grid;
    Chart.defaults.font.family = styles.getPropertyValue('--portal-font-ui').trim()
        || 'DM Sans, system-ui, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.tooltip.backgroundColor = styles.getPropertyValue('--portal-surface-2').trim() || '#161616';
    Chart.defaults.plugins.tooltip.titleColor = styles.getPropertyValue('--portal-text').trim() || '#f2f2f2';
    Chart.defaults.plugins.tooltip.bodyColor = text;
    Chart.defaults.plugins.tooltip.borderColor = grid;
    Chart.defaults.plugins.tooltip.borderWidth = 1;

    const lineOptions = (formatKes) => ({
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: true, position: 'top', align: 'end' },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
            },
            y: {
                beginAtZero: true,
                grid: { color: grid },
                ticks: {
                    precision: 0,
                    callback: formatKes
                        ? (value) => 'KES ' + Number(value).toLocaleString()
                        : (value) => value,
                },
            },
        },
    });

    const doughnutOptions = {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 14 } },
        },
    };

    const makeLine = (canvasId, datasets, formatKes) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        new Chart(canvas, {
            type: 'line',
            data: { labels: data.labels || [], datasets },
            options: lineOptions(formatKes),
        });
    };

    const isBidder = data.mode === 'bidder';

    makeLine('chart-bids', [
        {
            label: isBidder ? 'Bids placed' : 'Bids received',
            data: data.bids || [],
            borderColor: info,
            backgroundColor: hexToRgba(info, 0.12),
            fill: true,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 4,
            borderWidth: 2,
        },
        {
            label: isBidder ? 'Auctions bid on' : 'Unique bidders',
            data: isBidder ? (data.auctions || []) : (data.bidders || []),
            borderColor: success,
            backgroundColor: 'transparent',
            fill: false,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 4,
            borderWidth: 2,
        },
    ], false);

    makeLine('chart-revenue', [
        {
            label: isBidder ? 'Payments (KES)' : 'Paid revenue (KES)',
            data: data.revenue || [],
            borderColor: success,
            backgroundColor: hexToRgba(success, 0.12),
            fill: true,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 4,
            borderWidth: 2,
        },
    ], true);

    makeLine('chart-listings', [
        {
            label: isBidder ? 'Bid volume (KES)' : 'Auctions listed',
            data: data.listings || [],
            borderColor: warning,
            backgroundColor: hexToRgba(warning, 0.12),
            fill: true,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 4,
            borderWidth: 2,
        },
    ], isBidder);

    const statusCanvas = document.getElementById('chart-auction-status');
    if (statusCanvas) {
        const bidStatus = data.bidStatus;
        const statusLabels = bidStatus
            ? ['Winning', 'Outbid', 'Won', 'Withdrawn']
            : ['Active', 'Closed', 'Cancelled'];
        const statusData = bidStatus || data.auctionStatus || [0, 0, 0];
        const statusColors = bidStatus
            ? [success, warning, info, muted]
            : [info, success, muted];

        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: statusColors,
                    borderWidth: 0,
                }],
            },
            options: doughnutOptions,
        });
    }

    const payCanvas = document.getElementById('chart-payments');
    if (payCanvas) {
        new Chart(payCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Pending', 'Failed'],
                datasets: [{
                    data: data.payments || [0, 0, 0],
                    backgroundColor: [success, warning, error],
                    borderWidth: 0,
                }],
            },
            options: doughnutOptions,
        });
    }

    const catCanvas = document.getElementById('chart-category');
    if (catCanvas && (data.categories || []).length) {
        new Chart(catCanvas, {
            type: 'bar',
            data: {
                labels: (data.categories || []).map((row) => row.label),
                datasets: [{
                    label: isBidder ? 'Auctions' : 'Auctions',
                    data: (data.categories || []).map((row) => row.total),
                    backgroundColor: hexToRgba(info, 0.75),
                    borderRadius: 4,
                    barPercentage: 0.6,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: grid },
                        ticks: { precision: 0 },
                    },
                    y: { grid: { display: false } },
                },
            },
        });
    }
})();
