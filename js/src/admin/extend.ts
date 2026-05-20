import Extend from 'flarum/common/extenders';
import MagicSliderSettingsPage from './components/MagicSliderSettingsPage';

export default [
  new Extend.Admin().page(MagicSliderSettingsPage),
];