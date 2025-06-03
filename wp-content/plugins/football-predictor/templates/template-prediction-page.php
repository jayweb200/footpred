<?php
/**
 * Template Name: Prediction Page Template
 *
 * This is an example template for displaying match prediction data.
 * To use this:
 * 1. Place this file in your active theme's directory, or in a plugin directory.
 * 2. Create or edit a page in WordPress.
 * 3. In the 'Page Attributes' meta box, select 'Prediction Page Template' from the 'Template' dropdown.
 * 4. Access this page with URL parameters like:
 *    your-site.com/your-prediction-page/?match_id=123&home_team=TeamA&away_team=TeamB&league=SomeLeague&country=SomeCountry&date=YYYY-MM-DD
 */

// It's good practice to include WordPress header if this were a full theme template
// get_header();

?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <h1>Match Prediction</h1>

        <div style="padding: 10px; margin-bottom: 15px; border: 1px solid #ffcc00; background-color: #fff9e6;">
            <strong>Admin Note:</strong> This prediction page relies on several external APIs (for Football Stats, Odds, News, and AI Prediction).
            If data appears to be missing or is explicitly marked as "mocked", please ensure the necessary API keys (FOOTBALL_DATA_API_KEY, THE_ODDS_API_KEY, NEWS_API_ORG_KEY, GEMINI_API_KEY) are correctly configured in the plugin or wp-config.php.
        </div>

        <?php
        // Retrieve and sanitize URL parameters
        \$match_id = isset(\$_GET['match_id']) ? htmlspecialchars(\$_GET['match_id']) : 'N/A';
        \$home_team = isset(\$_GET['home_team']) ? htmlspecialchars(\$_GET['home_team']) : 'N/A';
        \$away_team = isset(\$_GET['away_team']) ? htmlspecialchars(\$_GET['away_team']) : 'N/A';
        \$league = isset(\$_GET['league']) ? htmlspecialchars(\$_GET['league']) : 'N/A';
        \$country = isset(\$_GET['country']) ? htmlspecialchars(\$_GET['country']) : 'N/A';
        \$match_date = isset(\$_GET['date']) ? htmlspecialchars(\$_GET['date']) : 'N/A'; // Renamed to avoid conflict with PHP date()

        // Display the retrieved information
        echo "<h2>Match Details (from URL)</h2>";
        echo "<ul>";
        echo "<li><strong>Match ID:</strong> " . \$match_id . "</li>";
        echo "<li><strong>Home Team:</strong> " . \$home_team . "</li>";
        echo "<li><strong>Away Team:</strong> " . \$away_team . "</li>";
        echo "<li><strong>League:</strong> " . \$league . "</li>";
        echo "<li><strong>Country:</strong> " . \$country . "</li>";
        echo "<li><strong>Date:</strong> " . \$match_date . "</li>";
        echo "</ul>";

        <!-- NewsAPI.org Integration -->
        <div id="news-api-data">
        <h3>Recent News (NewsAPI.org)</h3>
        <?php
        /**
         * Placeholder for your NewsAPI.org API Key.
         * IMPORTANT: For a real site, store this securely.
         * define('NEWS_API_ORG_KEY', 'YOUR_NEWS_API_KEY_HERE');
         */
        if (!defined('NEWS_API_ORG_KEY')) {
            define('NEWS_API_ORG_KEY', 'YOUR_NEWS_API_KEY_HERE'); // Default if not set elsewhere
        }

        /**
         * Fetches news articles from NewsAPI.org.
         *
         * @param string $query The search query (e.g., team name).
         * @param array $api_params Additional parameters for the API request.
         * @return array|WP_Error The decoded JSON response ('articles' array) or a WP_Error on failure.
         */
        function fmp_fetch_news_data($query, $api_params = array()) {
            if (NEWS_API_ORG_KEY === 'YOUR_NEWS_API_KEY_HERE' || empty(NEWS_API_ORG_KEY)) {
                error_log("NewsAPI.org Key is not set. Returning mock news data for query: " . $query);
                // Mock data structure
                return array(
                    'status' => 'ok',
                    'totalResults' => 2,
                    'articles' => array(
                        array(
                            'source' => array('name' => 'Mock News Source 1'),
                            'author' => 'John Doe',
                            'title' => "Mock Article: " . htmlspecialchars($query) . " Prepares for Big Match",
                            'description' => "A mock description of the upcoming match preparations for " . htmlspecialchars($query) . ".",
                            'url' => '#mock-article-1',
                            'urlToImage' => 'https://via.placeholder.com/100x70?text=News1',
                            'publishedAt' => date('Y-m-d\TH:i:s\Z', time() - 36000), // 10 hours ago
                            'content' => 'Mock content here...'
                        ),
                        array(
                            'source' => array('name' => 'Mock Sports Gazette'),
                            'author' => 'Jane Smith',
                            'title' => "Expert Analysis on " . htmlspecialchars($query) . "'s Chances",
                            'description' => "Mock expert insights into " . htmlspecialchars($query) . "'s current form and prospects.",
                            'url' => '#mock-article-2',
                            'urlToImage' => 'https://via.placeholder.com/100x70?text=News2',
                            'publishedAt' => date('Y-m-d\TH:i:s\Z', time() - 72000), // 20 hours ago
                            'content' => 'More mock content...'
                        ),
                    )
                );
            }

            // NewsAPI.org endpoint: https://newsapi.org/v2/everything OR /v2/top-headlines
            // Using /everything for more general search.
            $base_url = 'https://newsapi.org/v2/everything';

            $default_params = array(
                'q' => $query,
                'apiKey' => NEWS_API_ORG_KEY,
                'language' => 'en', // Get English articles
                'sortBy' => 'relevancy', // or 'publishedAt', 'popularity'
                'pageSize' => 5, // Number of articles to fetch
                // 'domains' => 'espn.com,skysports.com,bbc.co.uk/sport' // Example: limit to specific sources
            );
            $request_params = array_merge($default_params, $api_params);
            $request_url = add_query_arg($request_params, $base_url);

            $response = wp_remote_get($request_url, array(
                'timeout' => 20,
                'headers' => array('User-Agent' => 'WordPress Football Predictor Plugin') // Good practice for NewsAPI
            ));

            if (is_wp_error($response)) {
                error_log("NewsAPI.org HTTP Error: " . $response->get_error_message());
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("NewsAPI.org JSON Decode Error: " . json_last_error_msg() . " | Response: " . substr($body, 0, 200));
                return new WP_Error('json_decode_error', 'Error decoding JSON response from NewsAPI.org.');
            }

            if (isset($data['status']) && $data['status'] === 'error') {
                 error_log("NewsAPI.org API Error: " . (isset($data['code']) ? $data['code'] . " - " : "") . (isset($data['message']) ? $data['message'] : "Unknown error"));
                 return new WP_Error('news_api_error', isset($data['message']) ? $data['message'] : 'Unknown API error from NewsAPI.org', array('code' => isset($data['code']) ? $data['code'] : null));
            }

            return $data; // Should contain 'articles' array
        }

        // Example Usage:
        // \$home_team and \$away_team are from URL parameters. \$league and \$country also available.

        // Fetch news for Home Team
        echo "<h4>News related to " . htmlspecialchars($home_team) . "</h4>";
        // Construct a more specific query if desired:
        $home_team_query = htmlspecialchars($home_team) . " football " . (isset($league) ? htmlspecialchars($league) : '');
        $home_news_data = fmp_fetch_news_data($home_team_query);

        if (is_wp_error($home_news_data)) {
            echo "<p style=\"color: #c00; background-color: #ffe0e0; padding: 8px; border: 1px solid #c00;\">Error fetching news for " . htmlspecialchars($home_team) . ": " . htmlspecialchars($home_news_data->get_error_message()) . "</p>";
        } elseif (empty($home_news_data['articles'])) {
            echo "<p style=\"color: #555; background-color: #f0f0f0; padding: 8px; border: 1px solid #ccc;\">No recent news found for " . htmlspecialchars($home_team) . ".</p>";
            if (isset($home_news_data['message'])) { echo "<p>API Message: ".htmlspecialchars($home_news_data['message'])."</p>";}
        } else {
            echo "<ul>";
            foreach ($home_news_data['articles'] as $article) {
                echo "<li>";
                echo "<a href='" . esc_url($article['url']) . "' target='_blank' rel='noopener noreferrer'>" . htmlspecialchars($article['title']) . "</a>";
                echo " <span style='font-size:0.9em; color:#555;'>(" . htmlspecialchars($article['source']['name']) . " - " . htmlspecialchars(date('M j, Y', strtotime($article['publishedAt']))) . ")</span>";
                // Optional: Display description
                // if (!empty($article['description'])) {
                //    echo "<p style='font-size:0.9em;'>" . htmlspecialchars($article['description']) . "</p>";
                // }
                echo "</li>";
            }
            echo "</ul>";
        }

        // Fetch news for Away Team
        echo "<h4>News related to " . htmlspecialchars($away_team) . "</h4>";
        $away_team_query = htmlspecialchars($away_team) . " football " . (isset($league) ? htmlspecialchars($league) : '');
        $away_news_data = fmp_fetch_news_data($away_team_query);

        if (is_wp_error($away_news_data)) {
            echo "<p style=\"color: #c00; background-color: #ffe0e0; padding: 8px; border: 1px solid #c00;\">Error fetching news for " . htmlspecialchars($away_team) . ": " . htmlspecialchars($away_news_data->get_error_message()) . "</p>";
        } elseif (empty($away_news_data['articles'])) {
            echo "<p style=\"color: #555; background-color: #f0f0f0; padding: 8px; border: 1px solid #ccc;\">No recent news found for " . htmlspecialchars($away_team) . ".</p>";
             if (isset($away_news_data['message'])) { echo "<p>API Message: ".htmlspecialchars($away_news_data['message'])."</p>";}
        } else {
            echo "<ul>";
            foreach ($away_news_data['articles'] as $article) {
                echo "<li>";
                echo "<a href='" . esc_url($article['url']) . "' target='_blank' rel='noopener noreferrer'>" . htmlspecialchars($article['title']) . "</a>";
                echo " <span style='font-size:0.9em; color:#555;'>(" . htmlspecialchars($article['source']['name']) . " - " . htmlspecialchars(date('M j, Y', strtotime($article['publishedAt']))) . ")</span>";
                echo "</li>";
            }
            echo "</ul>";
        }
        ?>
        <p style="font-size:0.8em; color: #777;"><em>Note: News data is mocked if NewsAPI.org key is not configured. Real data requires a valid API key and is subject to usage limits. News search queries are basic.</em></p>
        </div>
        <!-- End NewsAPI.org Integration -->

        <!-- Gemini API Prediction Section -->
        <p style="font-style: italic; color: #555;">Attempting to generate AI prediction with Gemini, please wait... This might take a moment.</p>
        <div id="gemini-api-prediction">
        <h2>Match Prediction (Gemini API)</h2>
        <?php
        /**
         * Placeholder for your Gemini API Key.
         * IMPORTANT: For a real site, store this securely (e.g., wp-config.php or server environment variable).
         * define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
         */
        if (!defined('GEMINI_API_KEY')) {
            define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE'); // Default if not set elsewhere
        }

        /**
         * Generates a prediction using the Gemini API.
         *
         * @param array $match_details Basic info: home_team, away_team, league, country, date.
         * @param array $football_stats H2H, team forms. (Assumes data fetched from fmp_fetch_football_data).
         * @param array $odds_data Betting odds. (Assumes data fetched from fmp_fetch_odds_data).
         * @param array $news_articles News for home and away teams. (Assumes data from fmp_fetch_news_data).
         * @return array An array containing 'prediction_text' and 'prompt_sent', or an error.
         */
        function fmp_get_gemini_prediction($match_details, $football_stats_h2h, $football_stats_home, $football_stats_away, $odds_data, $home_news, $away_news) {

            // 1. Construct the prompt
            $prompt = "Analyze the following football match data and provide a prediction with reasoning.

";
            $prompt .= "== Match Details ==
";
            $prompt .= "Home Team: " . htmlspecialchars($match_details['home_team']) . "
";
            $prompt .= "Away Team: " . htmlspecialchars($match_details['away_team']) . "
";
            $prompt .= "League: " . htmlspecialchars($match_details['league']) . " (" . htmlspecialchars($match_details['country']) . ")
";
            $prompt .= "Date: " . htmlspecialchars($match_details['date']) . "

";

            $prompt .= "== Head-to-Head Statistics (Recent) ==
";
            if (!empty($football_stats_h2h) && !is_wp_error($football_stats_h2h) && isset($football_stats_h2h['head2head'])) {
                $h2h = $football_stats_h2h['head2head'];
                $prompt .= "Matches Played: " . htmlspecialchars($h2h['numberOfMatches']) . "
";
                $prompt .= htmlspecialchars($match_details['home_team']) . " Wins: " . htmlspecialchars($h2h['homeTeam']['wins']) . "
";
                $prompt .= "Draws: " . htmlspecialchars($h2h['homeTeam']['draws']) . "
";
                $prompt .= htmlspecialchars($match_details['away_team']) . " Wins: " . htmlspecialchars($h2h['awayTeam']['wins']) . "
";
                if (!empty($football_stats_h2h['matches'])) {
                    $prompt .= "Some recent results (Home vs Away):
";
                    foreach(array_slice($football_stats_h2h['matches'], 0, 2) as \$match) { // Show 2 recent
                         $prompt .= "- " . htmlspecialchars($match['homeTeam']['name']) . " " . $match['score']['fullTime']['homeTeam'] . " - " . $match['score']['fullTime']['awayTeam'] . " " . htmlspecialchars($match['awayTeam']['name']) . "
";
                    }
                }
            } else {
                $prompt .= "H2H data not available or error.
";
            }
            $prompt .= "
";

            $prompt .= "== Team Form (Last 5 Matches) ==
";
            if (!empty($football_stats_home) && !is_wp_error($football_stats_home) && isset($football_stats_home['form'])) {
                $prompt .= htmlspecialchars($match_details['home_team']) . " Form: " . htmlspecialchars($football_stats_home['form']) . "
";
            } else {
                $prompt .= htmlspecialchars($match_details['home_team']) . " Form data not available.
";
            }
            if (!empty($football_stats_away) && !is_wp_error($football_stats_away) && isset($football_stats_away['form'])) {
                $prompt .= htmlspecialchars($match_details['away_team']) . " Form: " . htmlspecialchars($football_stats_away['form']) . "
";
            } else {
                $prompt .= htmlspecialchars($match_details['away_team']) . " Form data not available.
";
            }
            $prompt .= "
";

            $prompt .= "== Betting Odds (Example from one bookmaker) ==
";
            if (!empty($odds_data) && !is_wp_error($odds_data) && isset($odds_data[0]['bookmakers'][0]['markets'][0]['outcomes'])) {
                $main_odds = $odds_data[0]['bookmakers'][0]['markets'][0]['outcomes'];
                foreach ($main_odds as $outcome) {
                    $prompt .= htmlspecialchars($outcome['name']) . ": " . htmlspecialchars($outcome['price']) . "
";
                }
            } else {
                $prompt .= "Betting odds not available or error.
";
            }
            $prompt .= "
";

            $prompt .= "== Recent News Headlines ==
";
            $prompt .= "For " . htmlspecialchars($match_details['home_team']) . ":
";
            if (!empty($home_news) && !is_wp_error($home_news) && !empty($home_news['articles'])) {
                foreach(array_slice($home_news['articles'], 0, 2) as \$article) { // Max 2 headlines
                    $prompt .= "- "" . htmlspecialchars($article['title']) . "" (Source: " . htmlspecialchars($article['source']['name']) . ")
";
                }
            } else {
                $prompt .= "No specific news found for " . htmlspecialchars($match_details['home_team']) . ".
";
            }
            $prompt .= "For " . htmlspecialchars($match_details['away_team']) . ":
";
            if (!empty($away_news) && !is_wp_error($away_news) && !empty($away_news['articles'])) {
                foreach(array_slice($away_news['articles'], 0, 2) as \$article) { // Max 2 headlines
                    $prompt .= "- "" . htmlspecialchars($article['title']) . "" (Source: " . htmlspecialchars($article['source']['name']) . ")
";
                }
            } else {
                $prompt .= "No specific news found for " . htmlspecialchars($match_details['away_team']) . ".
";
            }
            $prompt .= "
";

            $prompt .= "== Prediction Request ==
";
            $prompt .= "Based on all the above information (H2H, team form, betting odds, and news context), please provide a concise prediction for this match. Indicate the likely winner, a possible scoreline if you feel confident, and the key factors from the data that support your prediction. Explain your reasoning clearly.
";
            $prompt .= "Consider factors like current momentum, historical performance, perceived market sentiment from odds, and any impactful news (like injuries if mentioned).
";
            $prompt .= "Output format desired:
Prediction: [Winner/Draw]
Possible Score: [X-Y] (Optional)
Confidence: [High/Medium/Low]
Reasoning: [Detailed explanation]
";

            // 2. Check for API Key and make the call (or return mock response)
            if (GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE' || empty(GEMINI_API_KEY)) {
                error_log("Gemini API Key is not set. Returning mock prediction and the prompt.");
                return array(
                    'prediction_text' => "<strong>Gemini API Key Not Configured.</strong><br>A prediction would appear here if the API key was set.<br><br><strong>Simulated Action:</strong> The system would attempt to generate a prediction based on the compiled data.",
                    'prompt_sent' => $prompt, // Return the prompt for debugging/display
                    'is_mock' => true
                );
            }

            // --- Actual Gemini API Call Structure (Conceptual) ---
            // The specific endpoint and request body structure will depend on the Gemini API version and model.
            // This is a general example using a hypothetical 'generateContent' endpoint.
            // You would typically use Google's PHP client library for Gemini if available and suitable for WordPress.
            // If not, a direct HTTP request:

            // $gemini_api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . GEMINI_API_KEY; // Example URL
            $gemini_api_url = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent?key=' . GEMINI_API_KEY; // v1 endpoint


            $request_body = array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array('text' => $prompt)
                        )
                    )
                ),
                // Optional: Add generationConfig if needed (temperature, topK, etc.)
                // 'generationConfig' => array(
                //   'temperature' => 0.7,
                //   'topK' => 40,
                // )
            );

            $response = wp_remote_post($gemini_api_url, array(
                'method'    => 'POST',
                'headers'   => array('Content-Type' => 'application/json'),
                'body'      => json_encode($request_body),
                'timeout'   => 60, // Increased timeout for generative models
            ));

            if (is_wp_error($response)) {
                error_log("Gemini API HTTP Error: " . $response->get_error_message());
                return array('error' => "Gemini API HTTP Error: " . $response->get_error_message(), 'prompt_sent' => $prompt);
            }

            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Gemini API JSON Decode Error: " . json_last_error_msg() . " | Response: " . substr($response_body,0,500));
                return array('error' => 'Error decoding JSON response from Gemini API.', 'prompt_sent' => $prompt);
            }

            // Extracting text from Gemini's response structure
            // This depends on the exact response format from the model.
            // Typically, it's like: $response_data['candidates'][0]['content']['parts'][0]['text']
            if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
                $prediction_text = $response_data['candidates'][0]['content']['parts'][0]['text'];
                return array(
                    'prediction_text' => nl2br(htmlspecialchars($prediction_text)), // Format for HTML display
                    'prompt_sent' => $prompt,
                    'is_mock' => false,
                    'raw_response' => $response_data // For debugging
                );
            } elseif (isset($response_data['error'])) {
                error_log("Gemini API Error: " . $response_data['error']['message']);
                return array('error' => "Gemini API Error: " . $response_data['error']['message'], 'prompt_sent' => $prompt, 'raw_response' => $response_data);
            } else {
                 error_log("Gemini API returned unexpected response structure. Full response: " . $response_body);
                return array('error' => 'Gemini API returned an unexpected response structure.', 'prompt_sent' => $prompt, 'raw_response' => $response_data);
            }
        }

        // --- Gather all necessary data for Gemini ---
        // Basic match details (already available from URL parameters)
        \$gemini_match_details = array(
            'home_team' => \$home_team, 'away_team' => \$away_team,
            'league' => \$league, 'country' => \$country, 'date' => \$match_date
        );

        // Football Stats Data (variables should be set by previous API calls)
        // These are now expected to be in scope from the Football Data API section
        // \$h2h_data, \$home_team_data, \$away_team_data
        // Let's re-fetch them or ensure they are available. For simplicity, we assume they are in scope.
        // If not, they would need to be passed around or re-fetched.
        // For this example, we are assuming these variables are populated from the earlier sections:
        // $h2h_data (from fmp_fetch_football_data for H2H)
        // $home_team_stats (from fmp_fetch_football_data for home team)
        // $away_team_stats (from fmp_fetch_football_data for away team)

        // Odds Data (variable should be set by The Odds API call)
        // $odds_data

        // News Data (variables should be set by NewsAPI.org calls)
        // $home_news_data, $away_news_data


        // Call Gemini API
        \$prediction_result = fmp_get_gemini_prediction(
            \$gemini_match_details,
            isset(\$h2h_data) ? \$h2h_data : array(), // Pass H2H data
            isset(\$home_team_stats) ? \$home_team_stats : array(), // Pass Home team form data
            isset(\$away_team_stats) ? \$away_team_stats : array(), // Pass Away team form data
            isset(\$odds_data) ? \$odds_data : array(),      // Pass Odds data
            isset(\$home_news_data) ? \$home_news_data : array(), // Pass Home news
            isset(\$away_news_data) ? \$away_news_data : array()  // Pass Away news
        );

        if (isset(\$prediction_result['error'])) {
            echo "<p style='color:red;'><strong>Error generating prediction:</strong> " . htmlspecialchars(\$prediction_result['error']) . "</p>";
        } elseif (isset(\$prediction_result['prediction_text'])) {
            echo "<div>" . \$prediction_result['prediction_text'] . "</div>"; // nl2br is applied in the function if not mock
        }

        // Optionally display the prompt for debugging, especially if it's a mock response
        if (WP_DEBUG === true && isset(\$prediction_result['prompt_sent'])) {
            echo "<hr><h4>Gemini API Prompt (for debugging):</h4>";
            echo "<pre style='white-space: pre-wrap; word-wrap: break-word; background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>" . htmlspecialchars(\$prediction_result['prompt_sent']) . "</pre>";
        }
        if (WP_DEBUG === true && isset(\$prediction_result['raw_response'])) {
            echo "<h4>Gemini API Raw Response (for debugging):</h4>";
            echo "<pre style='white-space: pre-wrap; word-wrap: break-word; background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>" . htmlspecialchars(print_r(\$prediction_result['raw_response'], true)) . "</pre>";
        }

        ?>
        <p style="font-size:0.8em; color: #777;"><em>Note: Gemini API predictions require a valid API key. If not configured, a placeholder message and the constructed prompt are shown. The quality of the prediction depends heavily on the data quality and the prompt engineering.</em></p>
        </div>
        <!-- End Gemini API Prediction Section -->

        <!-- The Odds API Integration -->
        <div id="odds-api-data">
        <h3>Betting Odds (The Odds API)</h3>
        <?php
        /**
         * Placeholder for your The Odds API Key.
         * IMPORTANT: For a real site, store this securely.
         * define('THE_ODDS_API_KEY', 'YOUR_ODDS_API_KEY_HERE');
         */
        if (!defined('THE_ODDS_API_KEY')) {
            define('THE_ODDS_API_KEY', 'YOUR_ODDS_API_KEY_HERE'); // Default if not set elsewhere
        }

        /**
         * Fetches data from The Odds API.
         *
         * @param string $sport_key Sport key, e.g., 'soccer_epl'.
         * @param string $region Region, e.g., 'uk', 'eu', 'us'.
         * @param string $market Market, e.g., 'h2h'.
         * @param string $home_team_name For filtering events (experimental, API might not directly support name filtering well).
         * @param string $away_team_name For filtering events.
         * @return array|WP_Error The decoded JSON response or a WP_Error on failure.
         */
        function fmp_fetch_odds_data($sport_key, $region = 'eu', $market = 'h2h', $home_team_name = '', $away_team_name = '') {
            if (THE_ODDS_API_KEY === 'YOUR_ODDS_API_KEY_HERE' || empty(THE_ODDS_API_KEY)) {
                error_log("The Odds API Key is not set. Returning mock data.");
                // Mock data structure, assuming H2H market
                return array(
                    array(
                        'sport_title' => 'Mock Soccer League',
                        'home_team' => !empty($home_team_name) ? $home_team_name : 'Mock Home Team',
                        'away_team' => !empty($away_team_name) ? $away_team_name : 'Mock Away Team',
                        'bookmakers' => array(
                            array(
                                'key' => 'mockbookie1',
                                'title' => 'Mock Bookie 1',
                                'markets' => array(
                                    array(
                                        'key' => 'h2h',
                                        'outcomes' => array(
                                            array('name' => !empty($home_team_name) ? $home_team_name : 'Mock Home Team', 'price' => 1.75),
                                            array('name' => 'Draw', 'price' => 3.50),
                                            array('name' => !empty($away_team_name) ? $away_team_name : 'Mock Away Team', 'price' => 4.20),
                                        ),
                                    ),
                                ),
                            ),
                            array(
                                'key' => 'mockbookie2',
                                'title' => 'Mock Bookie 2',
                                'markets' => array(
                                    array(
                                        'key' => 'h2h',
                                        'outcomes' => array(
                                            array('name' => !empty($home_team_name) ? $home_team_name : 'Mock Home Team', 'price' => 1.80),
                                            array('name' => 'Draw', 'price' => 3.40),
                                            array('name' => !empty($away_team_name) ? $away_team_name : 'Mock Away Team', 'price' => 4.00),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'commence_time' => date('Y-m-d\TH:i:s\Z', time() + 86400), // Mock commence time: tomorrow
                    )
                ); // End mock data array
            }

            // API V4 endpoint: https://api.the-odds-api.com/v4/sports/{sport}/odds/?apiKey={YOUR_API_KEY}&regions={regions}&markets={markets}
            // Note: Event filtering by team names is not directly supported in the main odds endpoint.
            // You typically fetch all odds for a sport/league and then find your match.
            // Or use the /events endpoint to find a specific event ID first.
            // For simplicity, this example fetches all odds for a given sport_key.
            $base_url = 'https://api.the-odds-api.com/v4/sports/';
            $request_url = $base_url . rawurlencode($sport_key) . '/odds/';
            $query_args = array(
                'apiKey' => THE_ODDS_API_KEY,
                'regions' => $region,
                'markets' => $market,
                // 'eventIds' => '{EVENT_ID}' // If you found an event ID
                // 'dateFormat' => 'iso',
                // 'oddsFormat' => 'decimal',
            );
            $request_url = add_query_arg($query_args, $request_url);

            $response = wp_remote_get($request_url, array('timeout' => 20));

            if (is_wp_error($response)) {
                error_log("The Odds API HTTP Error: " . $response->get_error_message());
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("The Odds API JSON Decode Error: " . json_last_error_msg() . " | Response: " . substr($body, 0, 200));
                return new WP_Error('json_decode_error', 'Error decoding JSON response from The Odds API.');
            }

            // The Odds API returns an empty array [] if no games are found, which is not an error.
            // An actual error might be indicated by a 'message' field (e.g. for auth failure)
            if (isset($data['message'])) {
                 error_log("The Odds API Message: " . $data['message']);
                 return new WP_Error('odds_api_message', $data['message']);
            }

            // Filter for the specific match if team names are provided
            // This is a client-side filter as the API fetches all events for the sport_key
            if (!empty($home_team_name) && !empty($away_team_name) && is_array($data) && !empty($data)) {
                $filtered_data = array();
                foreach ($data as $event) {
                    if (isset($event['home_team']) && isset($event['away_team'])) {
                        // Normalize team names for comparison (simple version)
                        $api_home = strtolower(trim($event['home_team']));
                        $param_home = strtolower(trim($home_team_name));
                        $api_away = strtolower(trim($event['away_team']));
                        $param_away = strtolower(trim($away_team_name));

                        // Check if both teams match (can be tricky due to name variations)
                        if ((strpos($api_home, $param_home) !== false || strpos($param_home, $api_home) !== false) &&
                            (strpos($api_away, $param_away) !== false || strpos($param_away, $api_away) !== false)) {
                            $filtered_data[] = $event;
                        }
                    }
                }
                return $filtered_data; // Return only matching events or empty if none found
            }

            return $data; // Return all data if no team names for filtering or if data is not the expected array
        }

        /**
         * Helper to map a general league name to a sport_key for The Odds API.
         * This is highly simplified. A more robust solution would be needed.
         */
        function fmp_get_sport_key_from_league($league_name, $country_name) {
            $league_name_lower = strtolower($league_name);
            $country_name_lower = strtolower($country_name);

            if (strpos($league_name_lower, 'premier league') !== false && $country_name_lower === 'england') return 'soccer_epl';
            if (strpos($league_name_lower, 'championship') !== false && $country_name_lower === 'england') return 'soccer_efl_champ';
            if (strpos($league_name_lower, 'serie a') !== false && $country_name_lower === 'italy') return 'soccer_italy_serie_a';
            if (strpos($league_name_lower, 'liga') !== false && $country_name_lower === 'spain') return 'soccer_spain_la_liga';
            if (strpos($league_name_lower, 'bundesliga') !== false && $country_name_lower === 'germany') return 'soccer_germany_bundesliga';
            if (strpos($league_name_lower, 'ligue 1') !== false && $country_name_lower === 'france') return 'soccer_france_ligue_one';
            if (strpos($league_name_lower, 'champions league') !== false) return 'soccer_uefa_champs_league';
            // Add more mappings as needed
            return 'soccer_other'; // Default or fallback
        }

        // Example Usage:
        // \$league and \$country are from URL parameters. \$home_team and \$away_team also from URL.
        \$sport_key = fmp_get_sport_key_from_league(\$league, \$country);
        \$odds_data = fmp_fetch_odds_data(\$sport_key, 'eu', 'h2h', \$home_team, \$away_team);

        if (is_wp_error(\$odds_data)) {
            echo "<p style=\"color: #c00; background-color: #ffe0e0; padding: 8px; border: 1px solid #c00;\">Error fetching odds data: " . htmlspecialchars(\$odds_data->get_error_message()) . "</p>";
        } elseif (empty(\$odds_data)) {
            echo "<p style=\"color: #555; background-color: #f0f0f0; padding: 8px; border: 1px solid #ccc;\">No odds data found for this match or league (sport key: '" . htmlspecialchars(\$sport_key) . "'). This could be due to no current odds, or the league not being mapped correctly for The Odds API.</p>";
            if (THE_ODDS_API_KEY === 'YOUR_ODDS_API_KEY_HERE' || empty(THE_ODDS_API_KEY)) {
                 echo "<p><em>Note: The Odds API key is not configured, so mock data was attempted. The absence of data might be part of the mock response for this specific league/match combination.</em></p>";
            }
        } else {
            // Displaying data for the first match found (if multiple, or if not filtered)
            \$event = \$odds_data[0]; // Assuming the first event is the one we want after filtering or if only one returned
            echo "<h4>Odds for: " . htmlspecialchars(\$event['home_team']) . " vs " . htmlspecialchars(\$event['away_team']) . "</h4>";
            echo "<p><em>Match scheduled around: " . htmlspecialchars(date('M j, Y H:i T', strtotime(\$event['commence_time']))) . "</em></p>";
            echo "<ul>";
            foreach (\$event['bookmakers'] as \$bookmaker) {
                echo "<li><strong>" . htmlspecialchars(\$bookmaker['title']) . "</strong>";
                foreach (\$bookmaker['markets'] as \$market) {
                    if (\$market['key'] === 'h2h') { // Assuming we only care about H2H
                        echo "<ul>";
                        foreach (\$market['outcomes'] as \$outcome) {
                            echo "<li>" . htmlspecialchars(\$outcome['name']) . ": " . htmlspecialchars(\$outcome['price']) . "</li>";
                        }
                        echo "</ul>";
                    }
                }
                echo "</li>";
            }
            echo "</ul>";
        }
        ?>
        <p style="font-size:0.8em; color: #777;"><em>Note: Odds data is mocked if The Odds API key is not configured. Real data requires a valid API key and is subject to usage limits. League to sport key mapping is simplified. Team name matching for filtering can be complex.</em></p>
        </div>
        <!-- End The Odds API Integration -->

        <!-- Football Data API Integration -->
        <div id="football-data-api-stats">
        <h3>Football Statistics (football-data.org)</h3>
        <?php
        /**
         * Placeholder for your football-data.org API Key.
         * IMPORTANT: For a real site, store this securely, e.g., in wp-config.php or using WordPress options.
         * define('FOOTBALL_DATA_API_KEY', 'YOUR_API_KEY_HERE');
         */
        if (!defined('FOOTBALL_DATA_API_KEY')) {
            define('FOOTBALL_DATA_API_KEY', 'YOUR_API_KEY_HERE'); // Default if not set elsewhere
        }

        /**
         * Fetches data from football-data.org API.
         */
        function fmp_fetch_football_data($endpoint, $api_args = array()) {
            if (FOOTBALL_DATA_API_KEY === 'YOUR_API_KEY_HERE' || empty(FOOTBALL_DATA_API_KEY)) {
                error_log("Football Data API Key is not set. Returning mock data for endpoint: " . $endpoint);
                // Mock data logic based on endpoint
                if (strpos($endpoint, 'matches') !== false && isset($api_args['head2head'])) {
                     return array(
                        'head2head' => array(
                            'numberOfMatches' => 5, 'totalGoals' => 15,
                            'homeTeam' => array('wins' => 2, 'draws' => 1, 'losses' => 2, 'name' => 'Home Team'),
                            'awayTeam' => array('wins' => 2, 'draws' => 1, 'losses' => 2, 'name' => 'Away Team'),
                        ),
                        'matches' => array(
                            array('score' => array('fullTime' => array('homeTeam' => 2, 'awayTeam' => 1)), 'homeTeam' => array('name' => 'Mock Home'), 'awayTeam' => array('name' => 'Mock Away'), 'utcDate' => '2023-01-01'),
                            array('score' => array('fullTime' => array('homeTeam' => 0, 'awayTeam' => 0)), 'homeTeam' => array('name' => 'Mock Home'), 'awayTeam' => array('name' => 'Mock Away'), 'utcDate' => '2023-02-01'),
                        )
                    );
                } elseif (strpos($endpoint, 'teams/') !== false) { // Check if endpoint is for a specific team
                     // Extract team ID from endpoint like "teams/123"
                     preg_match('/teams\/(\d+)/', $endpoint, $matches);
                     $team_id_mock = isset($matches[1]) ? $matches[1] : (isset($api_args['team_id']) ? $api_args['team_id'] : 'Unknown');
                     return array(
                        'id' => $team_id_mock,
                        'name' => 'Mock Team ' . $team_id_mock,
                        'tla' => 'MT' . $team_id_mock,
                        'crestUrl' => 'https://via.placeholder.com/50',
                        'form' => 'W D L W W', // Example form
                        'activeCompetitions' => array(array('name' => 'Mock League')),
                    );
                }
                return array('message' => 'Mock data due to missing API key for endpoint: ' . $endpoint, 'data_type' => 'mock');
            }

            $base_url = 'https://api.football-data.org/v2/';
            $request_url = $base_url . ltrim($endpoint, '/'); // Ensure no double slashes

            if (!empty($api_args)) {
                // $request_url = add_query_arg($api_args, $request_url); // WordPress function, not available here directly
                // Manual query string building for non-WP context if needed, or ensure $api_args are part of endpoint string
            }

            $response = wp_remote_get($request_url, array(
                'headers' => array('X-Auth-Token' => FOOTBALL_DATA_API_KEY),
                'timeout' => 20,
            ));

            if (is_wp_error($response)) {
                error_log("Football Data API HTTP Error: " . $response->get_error_message());
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Football Data API JSON Decode Error: " . json_last_error_msg() . " | Response: " . substr($body, 0, 200));
                return new WP_Error('json_decode_error', 'Error decoding JSON response from Football Data API.');
            }

            if (isset($data['errorCode'])) {
                 error_log("Football Data API Error Code " . $data['errorCode'] . ": " . (isset($data['message']) ? $data['message'] : 'Unknown error'));
                 return new WP_Error('football_api_error', isset($data['message']) ? $data['message'] : 'Unknown API error', array('status' => $data['errorCode']));
            }
            return $data;
        }

        // Helper to get mock team ID based on name (very simplified)
        function fmp_get_mock_team_id_from_name($team_name_param) {
            // In a real app, you'd query the API or a local DB to map names to official IDs
            if (stripos($team_name_param, 'home') !== false || stripos($team_name_param, 'arsenal') !== false) return 57; // Example: Arsenal
            if (stripos($team_name_param, 'away') !== false || stripos($team_name_param, 'chelsea') !== false) return 61; // Example: Chelsea
            return rand(1,100); // fallback random ID
        }

        // Fetch and display H2H data
        // For football-data.org, H2H is often: /v2/matches?head2head=ID1-ID2 or /v2/teams/ID1/matches?againstTeam=ID2
        // For simplicity with mock data, we'll use a specific structure.
        // These IDs would be looked up from $home_team and $away_team names.
        $home_team_id_for_api = fmp_get_mock_team_id_from_name($home_team); // $home_team from URL params
        $away_team_id_for_api = fmp_get_mock_team_id_from_name($away_team); // $away_team from URL params

        // Corrected H2H endpoint structure for football-data.org (using one team's matches against another)
        // $h2h_endpoint = "teams/{$home_team_id_for_api}/matches?againstTeam={$away_team_id_for_api}&status=FINISHED";
        // Or, for the mock data structure:
        $h2h_data = fmp_fetch_football_data("matches", array('head2head' => "{$home_team_id_for_api}-{$away_team_id_for_api}"));

        if (!is_wp_error($h2h_data) && isset($h2h_data['head2head'])) {
            echo "<h4>Head-to-Head (" . htmlspecialchars($h2h_data['head2head']['homeTeam']['name']) . " vs " . htmlspecialchars($h2h_data['head2head']['awayTeam']['name']) . ")</h4>";
            echo "<ul>";
            echo "<li>Matches Played: " . htmlspecialchars($h2h_data['head2head']['numberOfMatches']) . "</li>";
            echo "<li>Total Goals: " . htmlspecialchars($h2h_data['head2head']['totalGoals']) . "</li>";
            echo "<li>" . htmlspecialchars($home_team) . " Wins: " . htmlspecialchars($h2h_data['head2head']['homeTeam']['wins']) . "</li>";
            echo "<li>Draws: " . htmlspecialchars($h2h_data['head2head']['homeTeam']['draws']) . "</li>";
            echo "<li>" . htmlspecialchars($away_team) . " Wins: " . htmlspecialchars($h2h_data['head2head']['awayTeam']['wins']) . "</li>";
            echo "</ul>";
            if (!empty($h2h_data['matches'])) {
                echo "<h5>Recent Meetings (Mocked Results):</h5><ul>";
                foreach(array_slice($h2h_data['matches'], 0, 3) as $match) {
                    echo "<li>" . htmlspecialchars(date("Y-m-d", strtotime($match['utcDate']))) . ": " . htmlspecialchars($match['homeTeam']['name']) . " " . htmlspecialchars($match['score']['fullTime']['homeTeam']) . " - " . htmlspecialchars($match['score']['fullTime']['awayTeam']) . " " . htmlspecialchars($match['awayTeam']['name']) . "</li>";
                }
                echo "</ul>";
            }
        } elseif (is_wp_error($h2h_data)) {
            echo "<p style=\"color: #c00; background-color: #ffe0e0; padding: 8px; border: 1px solid #c00;\">Error fetching H2H data: " . htmlspecialchars($h2h_data->get_error_message()) . "</p>";
        } else {
            echo "<p style=\"color: #555; background-color: #f0f0f0; padding: 8px; border: 1px solid #ccc;\">Could not retrieve H2H data. (API key might be missing or data format unexpected)</p>";
             if (isset($h2h_data['message'])) { echo "<p>API Message: ".htmlspecialchars($h2h_data['message'])."</p>"; }
        }

        // Fetch Home Team Form
        $home_team_stats = fmp_fetch_football_data("teams/{$home_team_id_for_api}");
        if (!is_wp_error($home_team_stats) && isset($home_team_stats['form'])) {
            echo "<h4>" . htmlspecialchars($home_team) . " Form (Last 5 - Mocked if no API key)</h4>";
            echo "<p>Form: " . htmlspecialchars($home_team_stats['form']) . "</p>";
            if(isset($home_team_stats['name'])) { echo "<p>Team Name (from API): ".htmlspecialchars($home_team_stats['name'])."</p>"; }
        } else {
            echo "<p style=\"color: #555; background-color: #f0f0f0; padding: 8px; border: 1px solid #ccc;\">Could not retrieve form data for " . htmlspecialchars($home_team) . ".</p>";
            if (is_wp_error($home_team_stats)) echo "<p>Error: ".htmlspecialchars($home_team_stats->get_error_message())."</p>";
            else if (isset($home_team_stats['message'])) { echo "<p>API Message: ".htmlspecialchars($home_team_stats['message'])."</p>"; }
        }

        // Fetch Away Team Form
        $away_team_stats = fmp_fetch_football_data("teams/{$away_team_id_for_api}");
        if (!is_wp_error($away_team_stats) && isset($away_team_stats['form'])) {
            echo "<h4>" . htmlspecialchars($away_team) . " Form (Last 5 - Mocked if no API key)</h4>";
            echo "<p>Form: " . htmlspecialchars($away_team_stats['form']) . "</p>";
            if(isset($away_team_stats['name'])) { echo "<p>Team Name (from API): ".htmlspecialchars($away_team_stats['name'])."</p>"; }
        } else {
            echo "<p style=\"color: #555; background-color: #f0f0f0; padding: 8px; border: 1px solid #ccc;\">Could not retrieve form data for " . htmlspecialchars($home_team) . ".</p>";
            echo "<p style=\"color: #555; background-color: #f0f0f0; padding: 8px; border: 1px solid #ccc;\">Could not retrieve form data for " . htmlspecialchars($away_team) . ".</p>";
            if (is_wp_error($away_team_stats)) echo "<p>Error: ".htmlspecialchars($away_team_stats->get_error_message())."</p>";
            else if (isset($away_team_stats['message'])) { echo "<p>API Message: ".htmlspecialchars($away_team_stats['message'])."</p>"; }
        }
        ?>
        <p style="font-size:0.8em; color: #777;"><em>Note: Football data is mocked if the API key for football-data.org is not configured. Real data fetching requires a valid API key and may be subject to rate limits. Team name to ID mapping is also highly simplified in this example.</em></p>
        </div>
        <!-- End Football Data API Integration -->

        echo "<hr>";
        echo "<h2>Next Steps:</h2>";
        echo "<p>This page is now receiving basic match data. The next steps will involve:</p>";
        echo "<ul>";
        echo "<li>Fetching detailed statistics from a football data API.</li>";
        echo "<li>Fetching odds from The Odds API.</li>";
        echo "<li>Fetching news from NewsAPI.org.</li>";
        echo "<li>Sending all data to Gemini AI for a prediction.</li>";
        echo "<li>Displaying all this information.</li>";
        echo "</ul>";

        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php
// It's good practice to include WordPress footer if this were a full theme template
// get_footer();
?>
