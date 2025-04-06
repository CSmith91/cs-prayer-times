document.addEventListener("DOMContentLoaded", function () {
    if (!window.ptmData || !ptmData.timestamps) {
        console.error("Prayer time data not available");
        return;
    }

    const nowTimestamp = Math.floor(new Date().getTime() / 1000 - new Date().getTimezoneOffset() * 60);

    const prayerTimes = [
        { name: 'Fajr', time: ptmData.timestamps['timestamp-fajr'], formattedTime: ptmData.iqamah_times.fajr },
        { name: 'Zuhr', time: ptmData.timestamps['timestamp-zuhr'], formattedTime: ptmData.iqamah_times.zuhr },
        { name: 'Asr', time: ptmData.timestamps['timestamp-asr'], formattedTime: ptmData.iqamah_times.asr },
        { name: 'Maghrib', time: ptmData.timestamps['timestamp-maghrib'], formattedTime: ptmData.iqamah_times.maghrib },
        { name: 'Isha', time: ptmData.timestamps['timestamp-isha'], formattedTime: ptmData.iqamah_times.isha }
    ];

    const lastPrayer = ptmData.timestamps['timestamp-isha'];

    if (lastPrayer > nowTimestamp) {
        const nextPrayer = prayerTimes.find(p => p.time > nowTimestamp) || prayerTimes[0];

        document.getElementById(`${nextPrayer.name}-cell`).classList.add('focus');

        function updateCountdown() {
            const now = Math.floor(new Date().getTime() / 1000 - new Date().getTimezoneOffset() * 60);
            const remaining = nextPrayer.time - now;

            if (remaining <= 0) {
                location.reload();
                return;
            }

            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;
            const formattedTimeWithoutSeconds = nextPrayer.formattedTime.split(':').slice(0, 2).join(':');


            document.getElementById('prayer-next').innerHTML =
                `Next Prayer: <strong>${nextPrayer.name} Iqamah</strong> at <strong>${formattedTimeWithoutSeconds}</strong>`;
            document.getElementById('prayer-countdown').innerHTML =
                `${hours}h ${minutes}m ${seconds}s`;
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();
    } else {
        document.getElementById('prayer-next').innerHTML =
            `There are no more prayers today.`;
        document.getElementById('prayer-countdown').classList.add('hide');

        const secondsUntilMidnight = (24 * 3600) - (nowTimestamp % (24 * 3600));
        setTimeout(() => location.reload(), secondsUntilMidnight * 1000);
    }
});
