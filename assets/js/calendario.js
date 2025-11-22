import { Calendar } from '@fullcalendar/core';
import LocaleEs from '@fullcalendar/core/locales/es';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
// opcional (si usas bundler)
// import '@fullcalendar/core/main.css';
// import '@fullcalendar/daygrid/main.css';

document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.calendar-container');
    if (!container) {
        console.error('No se encontró el elemento .calendar-container en la página.');
        return;
    }

    const calendar = new FullCalendar.Calendar(container, {
        plugins: [ FullCalendar.dayGridPlugin, FullCalendar.interactionPlugin, FullCalendar.listPlugin ],
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        events: [
            { id: '1', title: 'Partido: Equipo A vs Equipo B', start: '2025-11-10' },
            { id: '2', title: 'Partido: Equipo C vs Equipo D', start: '2025-11-15' }
        ],
        selectable: true,
        eventClick: (info) => console.log(info.event.title),
        select: (info) => console.log(info)
    });

    calendar.render();
});