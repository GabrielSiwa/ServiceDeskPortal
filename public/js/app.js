/**
 * Service Desk Portal - Client-side API and UI helpers
 */

/**
 * JSON-RPC 2.0 API client
 */
class APIClient {
  constructor(endpoint = "/api.php") {
    this.endpoint = endpoint;
  }

  /**
   * Make JSON-RPC 2.0 call
   * @param {string} method RPC method name
   * @param {object} params Method parameters
   * @returns {Promise} Result data
   */
  async call(method, params = {}) {
    const payload = {
      jsonrpc: "2.0",
      method: method,
      params: params,
      id: Date.now(),
    };

    try {
      const response = await fetch(this.endpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const data = await response.json();

      if (data.error) {
        throw new Error(data.error.message);
      }

      return data.result;
    } catch (err) {
      console.error("API Error:", err);
      throw err;
    }
  }
}

const api = new APIClient();
let toastContainer = null;
let lastAPICall = {};

/**
 * Get or create toast container element
 * @returns {HTMLElement}
 */
function getOrCreateToastContainer() {
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.id = "toast-container";
    document.body.appendChild(toastContainer);
  }
  return toastContainer;
}

/**
 * Display toast notification message
 * @param {string} message Message text
 * @param {string} type Type (success, danger, warning, info)
 */
function showToast(message, type = "info") {
  const container = getOrCreateToastContainer();

  const toast = document.createElement("div");
  toast.className = `toast-message ${type}`;

  toast.innerHTML = `
    <span>${message}</span>
    <button class="toast-close" aria-label="Close toast">×</button>
  `;

  const closeBtn = toast.querySelector(".toast-close");
  closeBtn.addEventListener("click", () => {
    toast.style.animation = "slideOut 0.3s ease-out";
    setTimeout(() => toast.remove(), 300);
  });

  container.appendChild(toast);

  // Auto-dismiss after 4 seconds
  setTimeout(() => {
    if (toast.parentNode) {
      toast.style.animation = "slideOut 0.3s ease-out";
      setTimeout(() => toast.remove(), 300);
    }
  }, 4000);
}

/**
 * Debounce function to prevent rapid API calls
 * @param {function} fn Function to debounce
 * @param {string} key Unique key for tracking calls
n * @param {number} delayMs Minimum delay between calls
 * @returns {function}
 */
function debounce(fn, key, delayMs = 1000) {
  return function (...args) {
    if (lastAPICall[key] && Date.now() - lastAPICall[key] < delayMs) {
      showToast("Please wait before trying again...", "warning");
      return;
    }
    lastAPICall[key] = Date.now();
    return fn(...args);
  };
}

const debouncedLoadTickets = debounce(loadTicketsViaAPI, "loadTickets", 1500);

/**
 * Load tickets via JSON-RPC API and update table
 * @param {object} filters Optional filter parameters
 */
async function loadTicketsViaAPI(filters = {}) {
  try {
    const tickets = await api.call("ticket.list", filters);

    const tbody = document.querySelector("#ticketsTable tbody");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (tickets.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="7" class="text-center text-muted">No tickets found.</td></tr>';
      return;
    }

    tickets.forEach((ticket) => {
      const priorityColor =
        ticket.priority === "critical"
          ? "danger"
          : ticket.priority === "high"
          ? "warning"
          : "info";

      const statusColor =
        ticket.status === "open"
          ? "danger"
          : ticket.status === "in_progress"
          ? "warning"
          : "success";

      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${ticket.id}</td>
        <td>${escapeHtml(ticket.title)}</td>
        <td><span class="badge bg-${priorityColor}">${escapeHtml(
        ticket.priority
      )}</span></td>
        <td><span class="badge bg-${statusColor}">${escapeHtml(
        ticket.status
      )}</span></td>
        <td>${
          ticket.assigned_to_name ? escapeHtml(ticket.assigned_to_name) : "—"
        }</td>
        <td>${ticket.asset_name ? escapeHtml(ticket.asset_name) : "—"}</td>
        <td><a href="/index.php?action=ticket-detail&id=${
          ticket.id
        }" class="btn btn-sm btn-outline-primary">View</a></td>
      `;
      tbody.appendChild(row);
    });

    showToast(`Loaded ${tickets.length} tickets via API`, "success");
  } catch (err) {
    showToast(`Error loading tickets: ${err.message}`, "danger");
  }
}

/**
 * Update ticket status via JSON-RPC API
 * @param {number} ticketId Ticket ID
 * @param {string} newStatus New status value
 */
async function updateTicketStatusViaAPI(ticketId, newStatus) {
  try {
    await api.call("ticket.updateStatus", {
      ticket_id: ticketId,
      status: newStatus,
    });

    showToast("Status updated via API", "success");
    setTimeout(() => location.reload(), 1000);
  } catch (err) {
    showToast(`Error updating status: ${err.message}`, "danger");
  }
}

/**
 * Assign ticket to technician via JSON-RPC API
 * @param {number} ticketId Ticket ID
 * @param {number|null} assignedToId User ID or null to unassign
 */
async function assignTicketViaAPI(ticketId, assignedToId) {
  try {
    await api.call("ticket.assign", {
      ticket_id: ticketId,
      assigned_to: assignedToId || null,
    });

    showToast("Assignment updated via API", "success");
    setTimeout(() => location.reload(), 1000);
  } catch (err) {
    showToast(`Error assigning ticket: ${err.message}`, "danger");
  }
}

/**
 * Escape HTML special characters (XSS prevention)
 * @param {string} unsafe Unsafe string
 * @returns {string} Escaped string
 */
function escapeHtml(unsafe) {
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
