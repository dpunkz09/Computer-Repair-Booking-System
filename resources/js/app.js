import Alpine from 'alpinejs';
import { notificationBell } from './notification-bell';
import { ticketConversation } from './ticket-conversation';

window.Alpine = Alpine;
Alpine.data('ticketConversation', ticketConversation);
Alpine.data('notificationBell', notificationBell);

Alpine.start();
