<?php
// Include site features configuration
require_once __DIR__ . '/site_features.php';
?>
<nav class="nav-bar">
  <ul class="nav-menu">
    <?php if (isFeatureEnabled('nav_home')): ?>
      <li><a class="nav-link" href="/">Home</a></li>
    <?php endif; ?>
    
    <?php if (isFeatureEnabled('nav_pilot_credits') && isFeatureEnabled('credits_enabled')): ?>
      <li><a class="nav-link" href="/pilot_credits">Pilot Credits</a></li>
    <?php endif; ?>
    
    <?php if (isFeatureEnabled('nav_leaderboard')): ?>
      <li><a class="nav-link" href="/leaderboard">Leaderboard</a></li>
    <?php endif; ?>
    
    <?php if (isFeatureEnabled('nav_pilot_statistics')): ?>
      <li><a class="nav-link" href="/pilot_statistics">Pilot Statistics</a></li>
    <?php endif; ?>
    
    <?php if (isFeatureEnabled('nav_squadrons') && isFeatureEnabled('squadrons_enabled')): ?>
      <li><a class="nav-link" href="/squadrons">Squadrons</a></li>
    <?php endif; ?>
    
    <?php if (isFeatureEnabled('nav_servers')): ?>
      <li><a class="nav-link" href="/servers">Servers</a></li>
    <?php endif; ?>
    
    <?php if (isFeatureEnabled('show_squadron_homepage') && !empty(getFeatureValue('squadron_homepage_url'))): ?>
      <li><a class="nav-link" href="<?= htmlspecialchars(getFeatureValue('squadron_homepage_url')) ?>"><?= htmlspecialchars(getFeatureValue('squadron_homepage_text', 'Squadron')) ?></a></li>
    <?php endif; ?>
    
    <?php if (isFeatureEnabled('show_discord_link')): ?>
      <li><a class="nav-link" href="<?= htmlspecialchars(getFeatureValue('discord_link_url', 'https://discord.gg/DNENf6pUNX')) ?>">Discord</a></li>
    <?php endif; ?>
  </ul>
</nav>
