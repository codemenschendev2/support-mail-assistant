<?php

declare(strict_types=1);

/**
 * Drafts List View
 * Displays list of draft emails with actions
 */

use helpers\Html;

$pageTitle = 'Danh sách Draft Emails';
$pageContent = '';

// Check if user is authenticated
if (!isset($_SESSION['access_token'])) {
    $pageContent = '
        <div class="alert alert-warning">
            <h4 class="alert-heading">Chưa đăng nhập!</h4>
            <p>Bạn cần đăng nhập Google để xem danh sách draft emails.</p>
            <hr>
            <a href="/support-mail-assistant/oauth/start.php" class="btn btn-primary">
                Đăng nhập Google
            </a>
        </div>
    ';
} else {
    $pageContent = '
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Danh sách Draft Emails</h1>

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">Draft Emails</h5>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary btn-sm" onclick="refreshDrafts()">
                                    <i class="bi bi-arrow-clockwise"></i> Làm mới
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="drafts-container">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Đang tải...</span>
                                </div>
                                <p class="mt-2">Đang tải danh sách draft...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Draft Actions Modal -->
        <div class="modal fade" id="draftModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Chi tiết Draft</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="draft-details"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" onclick="sendDraft()">Gửi Email</button>
                    </div>
                </div>
            </div>
        </div>
    ';

    // Add JavaScript for drafts functionality
    $pageContent .= '
        <script>
            let currentDraftId = null;

            // Load drafts on page load
            document.addEventListener("DOMContentLoaded", function() {
                loadDrafts();
            });

            function loadDrafts() {
                fetch("/support-mail-assistant/endpoints/list_drafts.php", {
                    method: "GET",
                    headers: {
                        "Content-Type": "application/json"
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayDrafts(data.data.drafts);
                    } else {
                        showAlert("Lỗi: " + (data.error || "Không thể tải danh sách draft"), "danger");
                    }
                })
                .catch(error => {
                    showAlert("Lỗi kết nối: " + error.message, "danger");
                });
            }

            function displayDrafts(drafts) {
                const container = document.getElementById("drafts-container");

                if (drafts.length === 0) {
                    container.innerHTML = \'<div class="text-center text-muted"><p>Không có draft email nào.</p></div>\';
                    return;
                }

                let html = \'<div class="table-responsive"><table class="table table-hover">\';
                html += \'<thead><tr><th>Chủ đề</th><th>Người nhận</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead><tbody>\';

                drafts.forEach(draft => {
                    html += \`<tr>
                        <td>\${draft.subject || \'<em>Không có chủ đề</em>\'}</td>
                        <td>\${draft.to || \'<em>Không có người nhận</em>\'}</td>
                        <td>\${draft.created}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewDraft(\'\${draft.id}\')">
                                Xem
                            </button>
                        </td>
                    </tr>\`;
                });

                html += \'</tbody></table></div>\';
                container.innerHTML = html;
            }

            function viewDraft(draftId) {
                currentDraftId = draftId;
                // Here you would fetch draft details and show in modal
                document.getElementById("draft-details").innerHTML = \'<p>Đang tải chi tiết draft...</p>\';
                document.getElementById("draftModal").classList.add("show");
                document.getElementById("draftModal").style.display = "block";
            }

            function sendDraft() {
                if (!currentDraftId) return;

                fetch("/support-mail-assistant/endpoints/send_draft.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        draft_id: currentDraftId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert("Đã gửi email thành công!", "success");
                        document.getElementById("draftModal").classList.remove("show");
                        document.getElementById("draftModal").style.display = "none";
                        loadDrafts(); // Refresh the list
                    } else {
                        showAlert("Lỗi: " + (data.error || "Không thể gửi email"), "danger");
                    }
                })
                .catch(error => {
                    showAlert("Lỗi kết nối: " + error.message, "danger");
                });
            }

            function refreshDrafts() {
                loadDrafts();
            }
        </script>
    ';
}

// Include the layout
require_once __DIR__ . '/layout.php';
