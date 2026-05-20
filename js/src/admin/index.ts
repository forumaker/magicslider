import app from 'flarum/admin/app';
import MagicSliderSettingsPage from './components/MagicSliderSettingsPage';

app.initializers.add('forumaker-magicslider', () => {
  app.extensionData.for('forumaker-magicslider').registerPage(MagicSliderSettingsPage);
});