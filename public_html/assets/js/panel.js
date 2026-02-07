document.addEventListener('DOMContentLoaded', () => {
  const panelId = document.body.dataset.panelId;
  const statusBox = document.getElementById('match-status');
  const teamA = document.getElementById('team-a');
  const teamB = document.getElementById('team-b');
  const crr = document.getElementById('crr');
  const rrr = document.getElementById('rrr');
  const partnership = document.getElementById('partnership');
  const required = document.getElementById('required');
  const ballsStrip = document.getElementById('balls-strip');

  const updateTeam = (teamEl, data) => {
    teamEl.querySelector('.team-name').textContent = data.name;
    teamEl.querySelector('.team-score').textContent = data.score;
    teamEl.querySelector('.team-overs').textContent = `${data.overs} ov`;
  };

  const updatePlayerCard = (cardId, player, type) => {
    const card = document.getElementById(cardId);
    if (!card || !player) {
      return;
    }
    card.querySelector('.player-name').textContent = player.name || type;
    if (type === 'Bowler') {
      card.querySelector('.player-score').textContent = player.figures || '0-0 (0.0)';
      card.querySelector('.stat-economy').textContent = player.economy || '0.0';
    } else {
      card.querySelector('.player-score').textContent = `${player.runs || 0} (${player.balls || 0})`;
      card.querySelector('.stat-fours').textContent = player.fours || 0;
      card.querySelector('.stat-sixes').textContent = player.sixes || 0;
      card.querySelector('.stat-sr').textContent = player.sr || '0.0';
    }
  };

  const renderBalls = (balls) => {
    ballsStrip.innerHTML = '';
    if (!balls || balls.length === 0) {
      const placeholder = document.createElement('span');
      placeholder.className = 'ball neutral';
      placeholder.textContent = 'Updating…';
      ballsStrip.appendChild(placeholder);
      return;
    }
    balls.forEach((ball) => {
      const el = document.createElement('span');
      const normalized = ball.toString().trim();
      el.className = 'ball neutral';
      if (/^4$/.test(normalized)) el.classList.add('four');
      if (/^6$/.test(normalized)) el.classList.add('six');
      if (/^w$/i.test(normalized)) el.classList.add('wicket');
      if (/wd|nb/i.test(normalized)) el.classList.add('extra');
      el.textContent = normalized;
      ballsStrip.appendChild(el);
    });
  };

  const updatePanel = (payload) => {
    if (!payload || !payload.data) {
      statusBox.textContent = 'Updating…';
      return;
    }
    const { data } = payload;
    updateTeam(teamA, data.teams[0]);
    updateTeam(teamB, data.teams[1]);
    statusBox.textContent = data.status || 'Updating…';
    crr.textContent = data.crr || '0.00';
    rrr.textContent = data.rrr || '0.00';
    partnership.textContent = data.partnership || '0 (0)';
    required.textContent = data.required || '0 runs (0 balls)';

    updatePlayerCard('batsman-1', data.batsmen?.[0], 'Batsman');
    updatePlayerCard('batsman-2', data.batsmen?.[1], 'Batsman');
    updatePlayerCard('bowler', data.bowler, 'Bowler');

    renderBalls(data.balls || []);
    statusBox.classList.add('pulse');
    setTimeout(() => statusBox.classList.remove('pulse'), 300);
  };

  const fetchData = () => {
    fetch(`fetch.php?panel_id=${encodeURIComponent(panelId)}`)
      .then((response) => response.json())
      .then(updatePanel)
      .catch(() => {
        statusBox.textContent = 'Updating…';
      });
  };

  fetchData();
  setInterval(fetchData, 1000);
});
