
    moment.locale('es');

    !function() {
      var today = moment();

      function Calendar(selector, events) {
        this.el = document.querySelector(selector);
        this.events = events;
        this.current = moment().date(1);
        this.draw();
        this.setupEventListeners();
        
        var current = document.querySelector('.today');
        if(current) {
          var self = this;
          window.setTimeout(function() {
            self.openDay(current);
          }, 500);
        }
      }

      Calendar.prototype.setupEventListeners = function() {
        var self = this;
        
        var addBtn = document.getElementById('addEventBtn');
        if(addBtn) {
          addBtn.addEventListener('click', function() {
            self.showModal();
          });
        }

        var saveBtn = document.getElementById('saveEvent');
        if(saveBtn) {
          saveBtn.addEventListener('click', function() {
            self.saveEvent();
          });
        }

        var cancelBtn = document.getElementById('cancelEvent');
        if(cancelBtn) {
          cancelBtn.addEventListener('click', function() {
            self.hideModal();
          });
        }

        var modal = document.getElementById('eventModal');
        if(modal) {
          modal.addEventListener('click', function(e) {
            if(e.target === modal) {
              self.hideModal();
            }
          });
        }
      }

      Calendar.prototype.showModal = function(event, dayNumber) {
        var modal = document.getElementById('eventModal');
        var form = document.getElementById('eventForm');
        var title = document.getElementById('modalTitle');
        
        if(event) {
            title.textContent = 'Editar Evento';
            document.getElementById('teamA').value = event.teamA || '';
            document.getElementById('teamB').value = event.teamB || '';
            document.getElementById('eventDate').value = event.date.format('YYYY-MM-DD');
            document.getElementById('eventTime').value = event.hour || '';
            document.getElementById('tournament').value = event.tournament || '';
            document.getElementById('categoria').value = event.categoria || '';
            document.getElementById('arbitros1').value = event.arbitros1 || '';
            document.getElementById('arbitros2').value = event.arbitros2 || '';
            document.getElementById('arbitros3').value = event.arbitros3 || '';
            document.getElementById('arbitros4').value = event.arbitros4 || '';
            document.getElementById('cancha').value = event.cancha || '';
            document.getElementById('eventColor').value = event.color || 'yellow';
            form.dataset.eventId = event.id;
        } else {
          title.textContent = 'Agregar Evento';
          form.reset();
          delete form.dataset.eventId;
          
          if(dayNumber) {
            var date = this.current.clone().date(dayNumber);
            document.getElementById('eventDate').value = date.format('YYYY-MM-DD');
          } else {
            document.getElementById('eventDate').value = moment().format('YYYY-MM-DD');
          }
        }
        
        modal.classList.add('active');
      }

      Calendar.prototype.hideModal = function() {
        var modal = document.getElementById('eventModal');
        modal.classList.remove('active');
      }

      Calendar.prototype.saveEvent = function() {
        var form = document.getElementById('eventForm');
        var eventId = form.dataset.eventId;
        
        var eventData = {
          teamA: document.getElementById('teamA').value.trim(),
          teamB: document.getElementById('teamB').value.trim(),
          date: moment(document.getElementById('eventDate').value),
          hour: document.getElementById('eventTime').value,
          tournament: document.getElementById('tournament').value.trim(),
          categoria: document.getElementById('categoria').value.trim(),
          arbitros1: document.getElementById('arbitros1').value.trim(),
          arbitros2: document.getElementById('arbitros2').value.trim(),
          arbitros3: document.getElementById('arbitros3').value.trim(),
          arbitros4: document.getElementById('arbitros4').value.trim(),
          cancha: document.getElementById('cancha').value.trim(),
          color: document.getElementById('eventColor').value,
        };

        if(!eventData.teamA || !eventData.teamB || !eventData.date.isValid() || !eventData.hour || !eventData.tournament) {
          alert('Por favor completa todos los campos');
          return;
        }

        if(eventId) {
          var event = this.events.find(function(e) { return e.id === eventId; });
          if(event) {
            event.teamA = eventData.teamA;
            event.teamB = eventData.teamB;
            event.date = eventData.date;
            event.hour = eventData.hour;
            event.tournament = eventData.tournament;
            event.categoria = eventData.categoria;
            event.arbitros1 = eventData.arbitros1;
            event.arbitros2 = eventData.arbitros2;
            event.arbitros3 = eventData.arbitros3;
            event.arbitros4 = eventData.arbitros4;
            event.cancha = eventData.cancha;
            event.color = eventData.color;
          }
        } else {
          eventData.id = 'evt_' + Date.now();
          this.events.push(eventData);
        }

        this.hideModal();
        this.draw();
      }

      Calendar.prototype.deleteEvent = function(eventId) {
        if(confirm('¿Estás seguro de eliminar este evento?')) {
          this.events = this.events.filter(function(e) { return e.id !== eventId; });
          this.draw();
        }
      }

      Calendar.prototype.draw = function() {
        this.el.innerHTML = '';
        this.drawHeader();
        this.drawMonth();
        this.drawLegend();
      }

      Calendar.prototype.drawHeader = function() {
        var self = this;
        this.header = createElement('div', 'header');
        
        this.title = createElement('h1');
        this.title.innerHTML = this.current.format('MMMM YYYY');

        var controls = createElement('div', 'controls');
        
        var left = createElement('div', 'left');
        left.addEventListener('click', function() { self.prevMonth(); });
        
        var right = createElement('div', 'right');
        right.addEventListener('click', function() { self.nextMonth(); });
        
        var addBtn = createElement('button', 'add-event-btn', '+ Agregar Evento');
        addBtn.id = 'addEventBtn';
        addBtn.addEventListener('click', function() { self.showModal(); });

        controls.appendChild(left);
        controls.appendChild(addBtn);
        controls.appendChild(right);
        
        this.header.appendChild(this.title);
        this.header.appendChild(controls);
        this.el.appendChild(this.header);
      }

      Calendar.prototype.drawMonth = function() {
        this.month = createElement('div', 'month');
        this.el.appendChild(this.month);
        this.backFill();
        this.currentMonth();
        this.fowardFill();
      }

      Calendar.prototype.backFill = function() {
        var clone = this.current.clone();
        var dayOfWeek = clone.day();

        if(!dayOfWeek) { return; }

        clone.subtract('days', dayOfWeek);

        for(var i = dayOfWeek; i > 0 ; i--) {
          this.drawDay(clone.add('days', 1));
        }
      }

      Calendar.prototype.fowardFill = function() {
        var clone = this.current.clone().add('months', 1).subtract('days', 1);
        var dayOfWeek = clone.day();

        if(dayOfWeek === 6) { return; }

        for(var i = dayOfWeek; i < 6 ; i++) {
          this.drawDay(clone.add('days', 1));
        }
      }

      Calendar.prototype.currentMonth = function() {
        var clone = this.current.clone();

        while(clone.month() === this.current.month()) {
          this.drawDay(clone);
          clone.add('days', 1);
        }
      }

      Calendar.prototype.getWeek = function(day) {
        if(!this.week || day.day() === 0) {
          this.week = createElement('div', 'week');
          this.month.appendChild(this.week);
        }
      }

      Calendar.prototype.drawDay = function(day) {
        var self = this;
        this.getWeek(day);

        var outer = createElement('div', this.getDayClass(day));
        outer.addEventListener('click', function() {
          self.openDay(this);
        });

        var name = createElement('div', 'day-name', day.format('ddd'));
        var number = createElement('div', 'day-number', day.format('DD'));
        var events = createElement('div', 'day-events');
        
        this.drawEvents(day, events);

        outer.appendChild(name);
        outer.appendChild(number);
        outer.appendChild(events);
        this.week.appendChild(outer);
      }

      Calendar.prototype.drawEvents = function(day, element) {
        if(day.month() === this.current.month()) {
          var todaysEvents = this.events.reduce(function(memo, ev) {
            if(ev.date.isSame(day, 'day')) {
              memo.push(ev);
            }
            return memo;
          }, []);

          todaysEvents.forEach(function(ev) {
            var evSpan = createElement('span', ev.color);
            element.appendChild(evSpan);
          });
        }
      }

      Calendar.prototype.getDayClass = function(day) {
        var classes = ['day'];
        if(day.month() !== this.current.month()) {
          classes.push('other');
        } else if (today.isSame(day, 'day')) {
          classes.push('today');
        }
        return classes.join(' ');
      }

      Calendar.prototype.openDay = function(el) {
        var dayNumber = +el.querySelector('.day-number').textContent;
        var day = this.current.clone().date(dayNumber);

        var currentOpened = document.querySelector('.details');
        if(currentOpened) {
          currentOpened.parentNode.removeChild(currentOpened);
        }

        var details = createElement('div', 'details');
        var arrow = createElement('div', 'arrow');

        details.appendChild(arrow);
        el.parentNode.appendChild(details);

        var todaysEvents = this.events.reduce(function(memo, ev) {
          if(ev.date.isSame(day, 'day')) {
            memo.push(ev);
          }
          return memo;
        }, []);

        this.renderEvents(todaysEvents, details, dayNumber);

        arrow.style.left = el.offsetLeft - el.parentNode.offsetLeft + 27 + 'px';
      }

      Calendar.prototype.renderEvents = function(events, ele, dayNumber) {
        var self = this;
        var wrapper = createElement('div', 'events');

        var addDayEventBtn = createElement('button', 'add-day-event', '+ Agregar evento este día');
        addDayEventBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          self.showModal(null, dayNumber);
        });
        wrapper.appendChild(addDayEventBtn);

        events.forEach(function(ev) {
          var div = createElement('div', 'event');
          var square = createElement('div', 'event-category ' + ev.color);
          var info = createElement('div', 'event-info');
          var name = createElement('div', 'event-name', ev.teamA + ' vs ' + ev.teamB + ' - ' + ev.hour + ' - ' + ev.tournament + '\n' + ev.categoria + ' - Cancha: ' + ev.cancha + '\nÁrbitros: ' + ev.arbitros1 + ', ' + ev.arbitros2 + ', ' + ev.arbitros3 + ', ' + ev.arbitros4);
          var cat = createElement('div', 'event-calendar', ev.calendar);
          info.appendChild(name);
          info.appendChild(cat);
          
          var actions = createElement('div', 'event-actions');
          
          var editBtn = createElement('button', 'edit-btn', 'Editar');
          editBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            self.showModal(ev);
          });
          
          var deleteBtn = createElement('button', 'delete-btn', 'Eliminar');
          deleteBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            self.deleteEvent(ev.id);
          });

          actions.appendChild(editBtn);
          actions.appendChild(deleteBtn);

          div.appendChild(square);
          div.appendChild(info);
          div.appendChild(actions);
          wrapper.appendChild(div);
        });

        if(!events.length) {
          var div = createElement('div', 'event empty');
          var span = createElement('span', '', 'No hay eventos este día');
          div.appendChild(span);
          wrapper.appendChild(div);
        }

        ele.appendChild(wrapper);
      }

      Calendar.prototype.drawLegend = function() {
        var legend = createElement('div', 'legend');
        var calendars = this.events.map(function(e) {
          return e.calendar + '|' + e.color;
        }).reduce(function(memo, e) {
          if(memo.indexOf(e) === -1) {
            memo.push(e);
          }
          return memo;
        }, []);
        
        calendars.forEach(function(e) {
          var parts = e.split('|');
          var entry = createElement('span', 'entry ' + parts[1], parts[0]);
          legend.appendChild(entry);
        });
        
        this.el.appendChild(legend);
      }

      Calendar.prototype.nextMonth = function() {
        this.current.add('months', 1);
        this.draw();
      }

      Calendar.prototype.prevMonth = function() {
        this.current.subtract('months', 1);
        this.draw();
      }

      window.Calendar = Calendar;

      function createElement(tagName, className, innerText) {
        var ele = document.createElement(tagName);
        if(className) {
          ele.className = className;
        }
        if(innerText) {
          ele.innerText = ele.textContent = innerText;
        }
        return ele;
      }
    }();

    function getCalendarStatus(color) {
        return color === "red"
        ? "sin asignar"
        : color === "green"
        ? "asignado"
        : "pendiente";
    }
    // Inicializar calendario con eventos de ejemplo
    !function() {
      var data = [
        { 
            id: 'evt_1',
            teamA: 'Equipo A',
            teamB: 'Equipo B',
            date: moment().date(5),
            hour: '15:00',
            tournament: 'Torneo 1',
            categoria: 'Categoria 1',
            arbitros1: 'Arbitro 1',
            arbitros2: 'Arbitro 2',
            arbitros3: 'Arbitro 3',
            arbitros4: 'Arbitro 4',
            cancha: 'Cancha 1',
            color: 'yellow'
        },
        { 
            id: 'evt_2',
            teamA: 'Equipo C',
            teamB: 'Equipo D',
            date: moment().date(12),
            hour: '18:00',
            tournament: 'Torneo 2',
            categoria: 'Categoria 2',
            arbitros1: 'Arbitro 5',
            arbitros2: 'Arbitro 6',
            arbitros3: 'Arbitro 7',
            arbitros4: 'Arbitro 8',
            cancha: 'Cancha 2',
            color: 'green'
        },
        { 
            id: 'evt_3',
            teamA: 'Equipo E',
            teamB: 'Equipo F',
            date: moment().date(20),
            hour: '20:00',
            tournament: 'Torneo 3',
            categoria: 'Categoria 3',
            arbitros1: 'Arbitro 9',
            arbitros2: 'Arbitro 10',
            arbitros3: 'Arbitro 11',
            arbitros4: 'Arbitro 12',
            cancha: 'Cancha 3',
            color: 'red'
        }
      ];
      // Calcular calendar para cada evento basado en su color
      data.forEach(function(event) {
        event.calendar = getCalendarStatus(event.color);
      });

      var calendar = new Calendar('#calendar', data);
    }();