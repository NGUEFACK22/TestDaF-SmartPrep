import './bootstrap';

// Bibliothèque de graphiques (tableaux de bord / statistiques).
import Chart from 'chart.js/auto';
window.Chart = Chart;

// Modules graphiques (évolution, progression par compétence).
import './charts';

// Moteur d'examen côté client (affichage seul : le serveur reste l'autorité).
import './exam';
