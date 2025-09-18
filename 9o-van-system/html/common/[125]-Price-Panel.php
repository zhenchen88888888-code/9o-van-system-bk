/**
 * Code Snippet: [125] 9O Booking - Price Panel HTML
 * 
 * Code Snippets 設定:
 * - Title: [125] 9O Booking - Price Panel HTML
 * - Description: 即時報價面板 HTML 模板
 * - Tags: 9o-booking, html, template, price
 * - Priority: 125
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    return;
}

// 註冊價格面板HTML模板
add_action('nineo_register_html_templates', 'nineo_register_price_panel_template');
function nineo_register_price_panel_template() {
    
    $price_panel_html = '<!-- 即時報價面板 - 共用組件 -->
<aside id="price-panel" class="price-panel" 
       role="complementary" aria-label="即時報價資訊">
    
    <h3>💰 即時報價</h3>
    
    <div class="price-content" role="region" aria-live="polite">
        <div class="price-loading">
            <div class="loading-state">
                <span class="spinner"></span>
                <span>請填寫表單資訊以獲得即時報價</span>
            </div>
        </div>
    </div>
    
    <!-- 價格說明 -->
    <div class="price-notes" style="display: none;">
        <small class="text-small">
            * 價格為預估金額，實際費用可能因路況、時間等因素調整
        </small>
    </div>
    
    <!-- 優惠提示 -->
    <div class="price-promotions" style="display: none;">
        <div class="promotion-item">
            <span class="promotion-icon">🎉</span>
            <span class="promotion-text">新客戶首次預約享9折優惠</span>
        </div>
    </div>
    
</aside>';

    // 註冊到全域模板變數
    nineo_register_html_template('shared/components/price-panel', $price_panel_html);
    
    if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
        error_log('[9O Booking] Price Panel HTML template registered');
    }
}
