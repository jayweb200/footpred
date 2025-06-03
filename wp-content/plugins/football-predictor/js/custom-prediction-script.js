document.addEventListener('DOMContentLoaded', function() {
    function waitForjQuery(callback) {
        if (typeof jQuery === "function") {
            callback(jQuery);
        } else {
            setTimeout(function () {
                waitForjQuery(callback);
            }, 100);
        }
    }

    waitForjQuery(function($) {
        function addPredictionButtons() {
            // Check if MutationObserver is available for more efficient detection
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                            $(mutation.addedNodes).find('tr[mid]').addBack('tr[mid]').each(function() {
                                var \$matchRow = $(this);
                                if (\$matchRow.next('.prediction-button-row').length === 0) {
                                    var mid = \$matchRow.attr('mid');
                                    var btnRow = \$('<tr class="prediction-button-row"><td colspan="6" style="text-align:center;"><button class="get-prediction-btn" data-match-id="' + mid + '" style="padding: 6px 12px; background: #0073aa; color: white; border: none; border-radius: 4px;">Get Prediction</button></td></tr>');
                                    \$matchRow.after(btnRow);
                                }
                            });
                        }
                    });
                });

                // Start observing the body for added Azscore content
                // Adjust the target node if Azscore content is loaded into a specific container
                var targetNode = document.body;
                observer.observe(targetNode, { childList: true, subtree: true });

                // Initial check for any already loaded rows
                 $('tr[mid]').each(function() {
                    var \$matchRow = $(this);
                    if (\$matchRow.next('.prediction-button-row').length === 0) {
                        var mid = \$matchRow.attr('mid');
                        var btnRow = \$('<tr class="prediction-button-row"><td colspan="6" style="text-align:center;"><button class="get-prediction-btn" data-match-id="' + mid + '" style="padding: 6px 12px; background: #0073aa; color: white; border: none; border-radius: 4px;">Get Prediction</button></td></tr>');
                        \$matchRow.after(btnRow);
                    }
                });

            } else {
                // Fallback to interval-based checking if MutationObserver is not available
                let interval = setInterval(function() {
                    if ($('tr[mid]').length > 0) {
                        $('tr[mid]').each(function() {
                            var \$matchRow = $(this);
                            if (\$matchRow.next('.prediction-button-row').length === 0) {
                                var mid = \$matchRow.attr('mid');
                                var btnRow = \$('<tr class="prediction-button-row"><td colspan="6" style="text-align:center;"><button class="get-prediction-btn" data-match-id="' + mid + '" style="padding: 6px 12px; background: #0073aa; color: white; border: none; border-radius: 4px;">Get Prediction</button></td></tr>');
                                \$matchRow.after(btnRow);
                            }
                        });
                        // Consider whether to clear this interval or let it run,
                        // depending on how Azscore loads data (e.g., AJAX updates vs. full page loads)
                        // For now, let's assume it might load more matches dynamically.
                        // clearInterval(interval); // Uncomment if matches are only loaded once.
                    }
                }, 1000); // Check every second
            }
        }

        // Initial call to add buttons
        addPredictionButtons();

        // Event delegation for click handling on dynamically added buttons
        $(document).on('click', '.get-prediction-btn', function() {
            var \$btn = $(this);
            var \$matchRow = \$btn.closest('tr.prediction-button-row').prev('tr[mid]');
            var \$table = \$btn.closest('table'); // Ensure this correctly finds the table
            var mid = \$btn.data('match-id');

            // Robustly find team names, league, country, and date
            // These selectors might need adjustment based on Azscore's actual HTML structure
            var homeTeam = \$matchRow.find('.az-home-team span').text().trim(); // Example: if team name is in a span
            if (!homeTeam) homeTeam = \$matchRow.find('td:nth-child(2)').text().trim(); // Fallback or adjust

            var awayTeam = \$matchRow.find('.az-away-team span').text().trim(); // Example
            if (!awayTeam) awayTeam = \$matchRow.find('td:nth-child(4)').text().trim(); // Fallback or adjust

            // For league and country, it's often better to find them relative to the specific match table
            // if Azscore has multiple tables for different leagues on one page.
            // The current JS snippet uses $table.find('.leagueHeader_league') which implies a single header.
            // This might need to be more specific if the structure is complex.
            var league = \$table.closest('div.azscore_widget').find('.leagueHeader_league').text().trim();
            var country = \$table.closest('div.azscore_widget').find('.leagueHeader_country').text().trim();

            // Date might be in a header row above the table or specific to a group of matches
            // This selector needs verification.
            var date = \$table.prevAll('.dateHeader').first().text().trim(); // Example: find closest preceding dateHeader
            if (!date) date = $('body').find('.dateHeader.active').text().trim(); // Or a global active date indicator

            var message =
                "Prediction request for:\n" +
                "Country: " + country + "\n" +
                "League: " + league + "\n" +
                "Match: " + homeTeam + " vs " + awayTeam + "\n" +
                "Date: " + date + "\n" + // Ensure date is correctly captured
                "Match ID: " + mid;

            alert(message);

            console.log({
                mid: mid,
                homeTeam: homeTeam,
                awayTeam: awayTeam,
                league: league,
                country: country,
                date: date // Ensure this is correctly populated
            });

            // --- RANDOM PAGE REDIRECT FUNCTIONALITY with URL Parameters ---
            var randomPages = [
                'http://18.237.183.73/page-1/',
                'http://18.237.183.73/page-2/',
                'http://18.237.183.73/page-3/',
                'http://18.237.183.73/page-3-2/'
            ];
            var randomIndex = Math.floor(Math.random() * randomPages.length);
            var baseUrl = randomPages[randomIndex];

            // Construct query parameters
            var queryParams = new URLSearchParams({
                match_id: mid,
                home_team: homeTeam,
                away_team: awayTeam,
                league: league,
                country: country,
                date: date
            }).toString();

            var randomUrl = baseUrl + '?' + queryParams;
            window.location.href = randomUrl;
            // --------------------------------------------------------------
        });
    });
});
