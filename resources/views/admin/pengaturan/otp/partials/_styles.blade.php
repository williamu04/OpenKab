<style  nonce="{{  csp_nonce() }}">
    .card-header.bg-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }
    .card-header.bg-success {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
    }
    .card-header.bg-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #545b62 100%) !important;
    }
    .custom-control-label {
        cursor: pointer;
    }
    .badge-lg {
        font-size: 0.9em;
        padding: 0.5em 0.8em;
    }
    .info-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid #007bff;
    }
    .alert.border-0 {
        border: none !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    code {
        font-size: 0.9em;
        color: #495057;
    }
    .badge.badge-light {
        background-color: #fff !important;
        border: 1px solid #28a745;
        font-weight: 600;
    }
    
    /* Animasi untuk tombol disable */
    #disableOtpBtn, #disable2faBtn {
        transition: all 0.3s ease;
    }
    
    #disableOtpBtn:hover, #disable2faBtn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220,53,69,0.3);
    }

    /* Animasi untuk info-box */
    .info-box {
        transition: all 0.3s ease;
    }

    .info-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Status badge animation */
    .badge.badge-light {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 rgba(40, 167, 69, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 rgba(40, 167, 69, 0); }
    }
    
    .otp-input {
        letter-spacing: 10px;
        font-size: 24px;
        font-family: 'Courier New', monospace;
        font-weight: bold;
        text-align: center;
        max-width: fit-content;
    }

     .card-header.bg-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
        }
        .card-header.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
        }
        .card-header.bg-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%) !important;
        }
        .custom-control-label {
            cursor: pointer;
        }
        .badge-lg {
            font-size: 0.9em;
            padding: 0.5em 0.8em;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #007bff;
        }
        .alert.border-0 {
            border: none !important;
            box-shadow: 0 2px 4px rgba(0,0,0.1);
        }
        code {
            font-size: 0.9em;
            color: #495057;
        }
        .badge.badge-light {
            background-color: #fff !important;
            border: 1px solid #28a745;
            font-weight: 600;
        }
        
        /* Animasi untuk tombol disable */
        #disableOtpBtn, #disable2faBtn {
            transition: all 0.3s ease;
        }
        
        #disableOtpBtn:hover, #disable2faBtn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(20,53,69,0.3);
        }

        /* Animasi untuk info-box */
        .info-box {
            transition: all 0.3s ease;
        }

        .info-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Status badge animation */
        .badge.badge-light {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 rgba(40, 167, 69, 0); }
        }
        
        .otp-input {
            letter-spacing: 10px;
            font-size: 24px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            text-align: center;
        }
</style>