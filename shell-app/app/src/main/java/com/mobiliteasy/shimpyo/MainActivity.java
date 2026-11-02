package com.mobiliteasy.shimpyo;

import android.annotation.SuppressLint;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.view.WindowManager;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.TextView;

import androidx.activity.OnBackPressedCallback;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

/**
 * 쉼표 웹뷰 셸.
 *
 * 화면 구성은 전부 웹(Vue)에서 하고, 셸은 네 가지만 책임진다.
 *   1) 허용된 호스트만 웹뷰 안에서 연다
 *   2) 뒤로가기를 웹 히스토리에 연결한다
 *   3) 충전 중 화면이 꺼지지 않게 한다
 *   4) 네트워크가 끊기면 안내를 띄운다
 */
public class MainActivity extends AppCompatActivity {

    private static final String START_URL = "https://shimpyo.revuplan.com/";
    private static final String ALLOWED_HOST_SUFFIX = ".revuplan.com";

    private WebView web;
    private View offline;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle saved) {
        super.onCreate(saved);
        setContentView(R.layout.activity_main);

        // 충전 대기 중에는 화면이 꺼지면 안 된다 (휴식 모드 타이머)
        getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);

        web = findViewById(R.id.web);
        offline = findViewById(R.id.offline);

        // 시스템 바 영역만큼 안쪽으로 밀어 콘텐츠가 가리지 않게 한다
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.root), (v, insets) -> {
            androidx.core.graphics.Insets bars =
                    insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(bars.left, bars.top, bars.right, bars.bottom);
            return insets;
        });

        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setLoadWithOverviewMode(true);
        s.setUseWideViewPort(true);
        s.setBuiltInZoomControls(false);
        s.setDisplayZoomControls(false);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setCacheMode(WebSettings.LOAD_DEFAULT);
        s.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);

        web.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView v, WebResourceRequest req) {
                Uri uri = req.getUrl();
                String host = uri.getHost();
                boolean ours = host != null
                        && (host.endsWith(ALLOWED_HOST_SUFFIX) || host.equals("revuplan.com"));
                if (ours) {
                    return false; // 웹뷰 안에서 연다
                }
                // 외부 링크는 기본 브라우저로 넘긴다
                try {
                    startActivity(new android.content.Intent(android.content.Intent.ACTION_VIEW, uri));
                } catch (Exception ignored) {
                }
                return true;
            }

            @Override
            public void onPageStarted(WebView v, String url, Bitmap favicon) {
                offline.setVisibility(View.GONE);
            }

            @Override
            public void onReceivedError(WebView v, WebResourceRequest req, WebResourceError err) {
                if (req.isForMainFrame()) {
                    offline.setVisibility(View.VISIBLE);
                }
            }
        });

        findViewById(R.id.retry).setOnClickListener(v -> {
            offline.setVisibility(View.GONE);
            web.loadUrl(START_URL);
        });

        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                if (web.canGoBack()) {
                    web.goBack();
                } else {
                    setEnabled(false);
                    getOnBackPressedDispatcher().onBackPressed();
                }
            }
        });

        if (saved != null) {
            web.restoreState(saved);
        } else {
            web.loadUrl(START_URL);
        }
    }

    @Override
    protected void onSaveInstanceState(Bundle out) {
        super.onSaveInstanceState(out);
        web.saveState(out);
    }
}
